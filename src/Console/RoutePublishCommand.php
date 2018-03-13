<?php

namespace KWRI\Kong\RoutePublisher\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use KWRI\Kong\RoutePublisher\KongPublisher;
use KWRI\Kong\RoutePublisher\RequestTransformer;
use KWRI\Kong\RoutePublisher\Oidc;
use KWRI\Kong\RoutePublisher\Jwt;
use KWRI\Kong\RoutePublisher\JwtClaimHeaders;
use KWRI\Kong\RoutePublisher\RouteBuilder;
use KWRI\Kong\RoutePublisher\PublisherBuilder;

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
        // Route 
        $routeOptions = [
            'app-name' => $this->argument('appName'),
            'remove-uri-prefix' => $this->option('remove-uri-prefix'),
            'upstream-host' => $this->option('upstream-host'),
        ];
        $routes = app()->make(RouteBuilder::class)->build($routeOptions);

        // Plugin
        $publisherPlugins = [
            'with-request-transformer' => $this->option('with-request-transformer'),
            'with-oidc' => $this->option('with-oidc'),
            'with-jwt' => $this->option('with-jwt'),
        ];
        $this->publisher = app(PublisherBuilder::class)->build($publisherPlugins);

        // Publish it
        $routes = $this->publisher->publishCollection($routes);
        $headers = $routes->first()->keys()->toArray();
        $this->table($headers, $routes);

    }
}
