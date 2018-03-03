<?php
namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client as GuzzleClient;
use KWRI\Kong\RoutePublisher\Console\RouteRefreshCommand;
use KWRI\Kong\RoutePublisher\Console\RoutePublishCommand;
use KWRI\Kong\RoutePublisher\Console\RouteDeleteCommand;
use KWRI\Kong\RoutePublisher\KongClient;

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

        $this->app->get('kong/publish-routes', function() {
            return response()->json()
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
