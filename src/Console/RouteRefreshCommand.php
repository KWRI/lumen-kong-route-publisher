<?php

namespace KWRI\Kong\RoutePublisher\Console;

use Illuminate\Console\Command;

class RouteRefreshCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'kong:refresh-route {appName}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Republish all registered routes to Kong (if all setup requirements found in .env).';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $app = $this->laravel;
        $appName = $this->argument('appName');
        if (env('KONG_PUBLISHER_JWT')
            && env('KONG_PUBLISHER_OIDC')
            && env('KONG_PUBLISHER_UPSTREAM_HOST')
            && env('KONG_PUBLISHER_REMOVED_URI_PREFIX')) {

            $this->call('kong:delete-route', [
                'appName' => $appName,
                '--no-interaction' => true
            ]);
            $this->call('kong:publish-route', [
                'appName' => $appName,
                '--upstream-host' => env('KONG_PUBLISHER_UPSTREAM_HOST'),
                '--remove-uri-prefix' => env('KONG_PUBLISHER_REMOVED_URI_PREFIX'),
                '--with-request-transformer' => true,
                '--with-jwt' => env('KONG_PUBLISHER_JWT'),
                '--with-oidc' => env('KONG_PUBLISHER_OIDC'),
                '--no-interaction' => true
            ]);
        }
    }
}
