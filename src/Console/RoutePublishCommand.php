<?php

namespace KWRI\Kong\RoutePublisher\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use KWRI\Kong\RoutePublisher\KongPublisher;
use KWRI\Kong\RoutePublisher\RequestTransformer;

class RoutePublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'kong:publish-route {appName} {--upstream-host=} '
        . '{--remove-uri-prefix=} {--with-request-transformer}';
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
            $uri = $firstRoute['uri'] == '/' ? '/api-info' : $firstRoute['uri'];
            $uri = $this->toPrefixedUrls($appName, $uri, $removeUriPrefix);
            $row = [
                'uris' => $uri,
                'upstream_url' => $this->getUpstreamUrl($firstRoute),
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


        if ($this->option('with-request-transformer')) {
            $this->publisher->attachBehavior($app->make(RequestTransformer::class));
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
        return $this->option('upstream-host') . $route['uri'];
    }

}
