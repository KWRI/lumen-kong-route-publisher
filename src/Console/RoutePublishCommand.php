<?php

namespace KWRI\Kong\RoutePublisher\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use KWRI\Kong\RoutePublisher\KongPublisher;
use KWRI\Kong\RoutePublisher\RequestTransformer;
use KWRI\Kong\RoutePublisher\Oidc;
use KWRI\Kong\RoutePublisher\Jwt;

class RoutePublishCommand extends Command
{
    /**
    * The console command name.
    *
    * @var string
    */
    protected $signature = 'kong:publish-route {appName} {--upstream-host=} '
    . '{--remove-uri-prefix=} {--with-request-transformer} {--with-oidc=} {--with-jwt=}';
    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Publish all registered routes to Kong.';


    /**
    * Execute the console command.
    *
    * @return void
    */
    public function fire()
    {
        $app = $this->laravel;
        $this->publisher = $app->make(KongPublisher::class);
        $appName = $this->normalizeUrlPrefix($this->argument('appName'));
        $removeUriPrefix = $this->normalizeUrlPrefix($this->option('remove-uri-prefix'));
        $routeCollection = new Collection($app->getRoutes());

        $rows = $routeCollection->groupBy(function ($route) {
            return $route['uri'];
        })
        ->map(function($routeGroup) use ($app, $appName, $removeUriPrefix){
            $firstRoute = $routeGroup->first();
            $middlewares = [];
            if (isset($firstRoute['action']['middleware'])) {
                $middlewares = $firstRoute['action']['middleware'];
            }

            $uri = $firstRoute['uri'] == '/' ? '/api-info' : $firstRoute['uri'];
            $uri = $this->toPrefixedUrls($appName, $uri, $removeUriPrefix);
            $row = [
                'uris' => $uri,
                'upstream_url' => $this->getUpstreamUrl($firstRoute),
                'middlewares' => implode(',',$middlewares),
            ];
            $row['name'] = $this->getRouteNameForRow($row);
            $methods = ['OPTIONS'];
            foreach ($routeGroup as $route) {
                $methods[] = $route['method'];
            }

            $row['methods'] = implode(',', $methods);

            return new Collection($row);
        })
        ->sortBy(function ($route) {
            return - substr_count($route['uris'], '/');
        });


        // Plugin sections
        // 1. Request transformer
        if ($this->option('with-request-transformer')) {
            $this->publisher->attachBehavior($app->make(RequestTransformer::class));
        }

        // 2. OIDC
        if ($this->option('with-oidc')) {
            list($clientId, $clientSecret,
            $discovery, $introspectionEndpoint,
            $authMethod) = explode(';', $this->option('with-oidc'));
            $oidc = new Oidc($clientId, $clientSecret, $discovery, $introspectionEndpoint, $authMethod);
            $this->publisher->attachBehavior($oidc);
        }

        // 3. JWT
        if ($this->option('with-jwt')) {
            $jwt = new Jwt($this->option('with-jwt'));
            $this->publisher->attachBehavior($jwt);
        }

        $rows = $this->publisher->publishCollection($rows);
        $headers = $rows->first()->keys()->toArray();
        $this->table($headers, $rows);

    }

    private function toPrefixedUrls($prefix, $url, $removeUriPrefix = '')
    {
        $url = $this->removeUriPrefix($url, $removeUriPrefix);
        if (Str::startsWith($url, $prefix)) {
            return $url;
        }
        return $prefix . $url;
    }

    private function removeUriPrefix($url, $removeUriPrefix)
    {

        if (Str::startsWith($url, $removeUriPrefix)) {
            $url = Str::replaceFirst($removeUriPrefix, '', $url);
        }

        return $url;
    }

    private function normalizeUrlPrefix($appName)
    {
        if ($appName{0} == '/') {
            return $appName;
        }

        return '/'.$appName;
    }
    /**
    * @param array $action
    * @return string
    */
    private function getRouteNameForRow(array $row)
    {
        $name = Str::lower(ltrim($row['uris'], '/'));
        $name = str_replace('/', '.', $name);
        $name = preg_replace_callback('#\{(.*?)\}#', function ($match) {
            preg_match('#.+?(?=:)#', last($match), $matches);
            return last($matches);
        }, $name);

        return $name;
    }

    private function getUpstreamUrl($route)
    {
        return rtrim($this->option('upstream-host'), '/') . $route['uri'];
    }

}
