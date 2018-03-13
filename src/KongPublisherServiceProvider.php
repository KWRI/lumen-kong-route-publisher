<?php
namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client as GuzzleClient;
use KWRI\Kong\RoutePublisher\Console\RouteRefreshCommand;
use KWRI\Kong\RoutePublisher\Console\RoutePublishCommand;
use KWRI\Kong\RoutePublisher\Console\RouteDeleteCommand;
use KWRI\Kong\RoutePublisher\KongClient;
use KWRI\Kong\RoutePublisher\RouteBuilder;
use Illuminate\Http\Request;

class KongPublisherServiceProvider extends ServiceProvider
{
    const KONG_ADMIN_HOST = 'KONG_ADMIN_HOST';
    const KONG_PUBLISHER_UPSTREAM_HOST = 'KONG_PUBLISHER_UPSTREAM_HOST';
    const KONG_PUBLISHER_REMOVED_URI_PREFIX = 'KONG_PUBLISHER_REMOVED_URI_PREFIX';

    public function boot()
    {
        $appName = file_get_contents(app()->basePath().'/.kong-app-name');

        // endpoints for generating kong payload
        app()->get('kong/delete-routes', function() use($appName) {
            $client = app()->make(KongClient::class);
            return response()->json([
                'meta' => [
                    'app_name' => $appName,
                    'admin_host' => getenv(self::KONG_ADMIN_HOST),
                ],
                'data' => $client->getApiByName($appName)
            ]);
        });

        $this->app->get('kong/publish-routes', function(Request $request) use($appName) {
            // Route
            $routeOptions = [
                'app-name' => $appName,
                'remove-uri-prefix' => getenv(self::KONG_PUBLISHER_REMOVED_URI_PREFIX),
                'upstream-host' => getenv(self::KONG_PUBLISHER_UPSTREAM_HOST),
            ];
            $routes = app()->make(RouteBuilder::class)->build($routeOptions);

            // Plugin
            $publisherPlugins = [
                'with-request-transformer' => $request->get('with-request-transformer', null),
                'with-oidc' => $request->get('with-oidc', null),
                'with-jwt' => $request->get('with-jwt', null),
            ];
            $this->publisher = app(PublisherBuilder::class)->build($publisherPlugins);

            // Publish it
            return $this->publisher->transformToPayload($routes);
        });
    }

    public function register()
    {
        $this->app->bind(KongClient::class, function() {
            $hosts = getenv(self::KONG_ADMIN_HOST);
            $client = new GuzzleClient(['base_uri' => $hosts]);
            return new KongClient($client);
        });

        $this->app->singleton('kong.route-refresh', RouteRefreshCommand::class);
        $this->app->singleton('kong.route-publisher', RoutePublishCommand::class);
        $this->app->singleton('kong.route-destroyer', RouteDeleteCommand::class);

        $this->commands(
          'kong.route-refresh',
          'kong.route-publisher',
          'kong.route-destroyer'
        );
    }

}
