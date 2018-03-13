<?php
namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\Collection;

class KongPublisher
{
    private $behaviors = [];

    public function __construct(KongClient $client)
    {
        $this->client = $client;
    }

    public function attachBehavior(BehaviorInterface $behavior)
    {
        $this->behaviors[] = $behavior;
    }

    public function publishCollection(Collection $routePayloads)
    {
        $results = $routePayloads->map(function ($payload) {
            try {
                $this->beforePublish($payload);
                $data = $payload->toArray();
                $response = $this->client->updateOrAddApi($data);
                $this->afterPublish($response, $payload);
                return $payload;
            } catch (\Exception $e) {
                $payload->offsetSet('error', $e->getMessage());
                return $payload;
            }
         });

        return $results;
    }

    public function transformToPayload(Collection $routePayloads)
    {
        return $routePayloads->map(function ($payload) {
            $this->beforePublish($payload);
            return [
                'route' => $payload->toArray(),
                'plugins' => $this->getPluginsPayload($payload)
            ];
        });
    }

    public function beforePublish($payload)
    {
        foreach ($this->behaviors as $behavior) {
            $behavior->transformPayload($payload);
        }
    }

    public function afterPublish($response, $payload)
    {
        if (empty($response)) {
            return;
        }

        if (!($response->getStatusCode() === 201 || $response->getStatusCode() === 200)) {
            return;
        }

        foreach ($this->behaviors as $behavior) {
            $behavior->activatePlugin($this->client, $payload, $response);
        }
    }

    public function getPluginsPayload($payload)
    {
        return collect($this->behaviors)->map(function($behavior) use ($payload) {
            return $behavior->createActivatePluginPayload($payload);
        })->toArray();
    }
}
