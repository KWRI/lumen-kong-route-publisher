<?php
namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client as GuzzleClient;

class KongPublisherServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(KongClient::class, function() {
            $hosts = getenv('KONG_ADMIN_HOST');
            $client = new GuzzleClient(['base_uri' => $hosts]);
            return new KongClient($client);
        });

    }
}
