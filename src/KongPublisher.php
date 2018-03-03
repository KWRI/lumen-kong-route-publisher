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

    public function publishCollection(Collection $routes)
    {

        $results = $routes->map(function ($route) {
            try {
                $this->beforePublish($route);
                $data = $route->toArray();
                $response = $this->client->updateOrAddApi($data);
                $this->afterPublish($response, $route);
                return $route;
            } catch (\Exception $e) {
                $route->offsetSet('error', $e->getMessage());
                return $route;
            }
         });

        return $results;
    }

    public function transformToPayload(Collection $routes)
    {

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
}
