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

    public function publishCollection(Collection $payloads)
    {

        $results = $payloads->map(function ($payload) {
            $this->beforePublish($payload);
            try {
                $response = $this->client->updateOrAddApi($payload->toArray());
                $this->afterPublish($response, $payload);
                return $payload;
            } catch (\Exception $e) {}
         });

        return $results;
    }

    public function beforePublish($payload)
    {
        foreach ($this->behaviors as $behavior) {
            $behavior->transformPayload($payload);
        }

    }

    public function afterPublish($response, $payload)
    {
        if (!($response->getStatusCode() === 201 || $response->getStatusCode() === 200)) {
            return;
        }

        foreach ($this->behaviors as $behavior) {
            $behavior->activatePlugin($this->client, $payload, $response);
        }
    }
}
