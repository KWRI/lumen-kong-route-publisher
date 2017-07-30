<?php
namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client as GuzzleClient;
use KWRI\Kong\RoutePublisher\Console\RoutePublishCommand;
use KWRI\Kong\RoutePublisher\Console\RouteDeleteCommand;

class KongPublisherServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(KongClient::class, function() {
            $hosts = getenv('KONG_ADMIN_HOST');
            $client = new GuzzleClient(['base_uri' => $hosts]);
            return new KongClient($client);
        });

        $this->app->singleton('kong.route-publisher', RoutePublishCommand::class);
        $this->app->singleton('kong.route-destroyer', RouteDeleteCommand::class);

        $this->commands(
          'kong.route-publisher',
          'kong.route-destroyer'
        );

    }
}
