<?php

namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Build route array for kong based on lumen route.
 */
class RouteBuilder
{
    public function build(array $options = [])
    {
        $app = app();
        $appName = $this->normalizeUrlPrefix($options['appName'] ?? null);
        $removeUriPrefix = $this->normalizeUrlPrefix($options['remove-uri-prefix'] ?? null);
        $upstreamHost = $this->normalizeUrlPrefix($options['upstream-host'] ?? null);
        $routeCollection = new Collection($app->getRoutes());

        return $routeCollection
            ->groupBy(function ($route) {
                return $route['uri'];
            })
            ->map(function($routeGroup) use ($app, $appName, $removeUriPrefix, $upstreamHost){
                $firstRoute = $routeGroup->first();
                $middlewares = [];
                if (isset($firstRoute['action']['middleware'])) {
                    $middlewares = $firstRoute['action']['middleware'];
                }

                $uri = $firstRoute['uri'] == '/' ? '/api-info' : $firstRoute['uri'];
                $uri = $this->toPrefixedUrls($appName, $uri, $removeUriPrefix);
                $route = [
                    'uris' => $uri,
                    'upstream_url' => $this->getUpstreamUrl($firstRoute, $upstreamHost),
                    'middlewares' => implode(',',$middlewares),
                ];
                $route['name'] = $this->getRouteNameForRow($route);
                $methods = ['OPTIONS'];
                foreach ($routeGroup as $rg) {
                    $methods[] = $rg['method'];
                }

                $route['methods'] = implode(',', $methods);

                return new Collection($route);
            })
            ->sortBy(function ($route) {
                return - substr_count($route['uris'], '/');
            });
    }

    private function normalizeUrlPrefix($appName)
    {
        if ($appName{0} == '/') {
            return $appName;
        }

        return '/'.$appName;
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

    private function getUpstreamUrl($route, $upstreamHost = null)
    {
        return trim(rtrim($upstreamHost, '/'),'/') . $route['uri'];
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
}
