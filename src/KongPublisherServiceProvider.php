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
    public function boot()
    {
        // endpoints for generating kong payload
        app()->get('kong/delete-routes', function() {
            $appName = getenv('KONG_APP_NAME');
            $client = app()->make(KongClient::class);
            return response()->json([
                'meta' => [
                    'app_name' => getenv('KONG_APP_NAME'),
                    'admin_host' => getenv('KONG_ADMIN_HOST'),
                ],
                'data' => $client->getApiByName($appName)
            ]);
        });

        $this->app->get('kong/publish-routes', function(Request $request) {
            // Route 
            $routeOptions = [
                'app-name' => getenv('KONG_APP_NAME'),
                'remove-uri-prefix' => getenv('KONG_REMOVE_URI_PREFIX'),
                'upstream-host' => getenv('KONG_UPSTREAM_HOST'),
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
            $hosts = getenv('KONG_ADMIN_HOST');
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
