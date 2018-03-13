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
        try {
            $this->client = $this->laravel->make(KongClient::class);
            $matchApi = $this->client->getApiByName($this->argument('appName'));
            $headers = array_keys($matchApi->first());
            $this->table($headers, $matchApi);
            if ($this->option('no-interaction') || $this->confirm('Do you want to delete all above routines from kong?')) {
                $matchApi->each(function ($api) {
                    $this->info('Deleting .. ' . $api['name']);
                    $this->client->delete($api);
                });
            }
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return;
        } catch (\UnexpectedValueException $e) {
            $this->info($e->getMessage());
            return;
        }
    }
}
