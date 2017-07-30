<?php

namespace KWRI\Kong\RoutePublisher\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use KWRI\Kong\RoutePublisher\KongClient;

class RouteDeleteCommand extends Command
{
    private $client;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'kong:delete-route {appName}';

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
        $this->client = $this->laravel->make(KongClient::class);
        $apis = $this->client->getApiList();
        if ($apis->getStatusCode() !== 200) {
            $this->error("Invalid response code {$apis->getStatusCode()}");
            return;
        }
        $apis = json_decode($apis->getBody(), true);
        $apiCollection = new Collection($apis['data']);
        $apiCollection = $this->mergeWithNextApis($apis, $apiCollection);

        $matchApi = $apiCollection->filter(function ($api) {
            return Str::startsWith($api['name'].'.', $this->argument('appName'));
        })->map(function($api) {
            return [
                'id' => $api['id'],
                'name' => $api['name'],
                'uris' => implode(',', $api['uris'])
            ];
        });
        if ($matchApi->isEmpty()) {
            $this->info("no routine found with name started by {$this->argument('appName')}");
            return;
        }
        $headers = array_keys($matchApi->first());
        $this->table($headers, $matchApi);
        if ($this->confirm('Do you want to delete all above routines from kong?')) {
            $matchApi->each(function ($api) {
                $this->info('Deleting .. ' . $api['name']);
                // $this->client->delete($api);
            });
        }



    }

    private function mergeWithNextApis(array $apis, Collection $apiCollection)
    {
        if (!isset($apis['next'])) {

            return $apiCollection;
        }

        $queryString = parse_url($apis['next'], PHP_URL_QUERY);
        parse_str($queryString, $queryParams);

        $apis = $this->client->getApiList($queryParams);
        $apis = json_decode($apis->getBody(), true);
        $apiCollection = $apiCollection->merge($apis['data']);
        return $this->mergeWithNextApis($apis, $apiCollection);

    }
}
