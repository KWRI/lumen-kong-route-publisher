<?php
namespace KWRI\Kong\RoutePublisher;

use Illuminate\Support\Collection;

interface BehaviorInterface {
    public function transformPayload(Collection $payload);

    public function activatePlugin(KongClient $client, Collection $payload, $response = null);
}
