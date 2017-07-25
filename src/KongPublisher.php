<?php
namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\Collection;

class KongPublisher
{
    private $behaviors = [];

    public function attachBehavior(BehaviorInterface $behavior)
    {
        $this->behaviors[] = $behavior;
    }

    public function publishCollection(Collection $payloads)
    {
        $results = $payloads->map(function ($payload) {
            $payload = $this->applyBehaviors($payload);
            return $payload;
         });

        return $results;
    }

    public function applyBehaviors($payload)
    {
        foreach ($this->behaviors as $behavior) {
            $payload = $behavior->transformPayload($payload);
        }
        
        return $payload;
    }
}
