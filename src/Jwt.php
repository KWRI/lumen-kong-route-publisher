<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Jwt implements BehaviorInterface
{
    const PLUGIN_NAME = 'jwt';

    public function transformPayload(Collection $payload)
    {
        return $payload;
    }

    public function activatePlugin(KongClient $client, Collection $payload, $response = null)
    {
        $client->updateOrAddPlugin($payload->offsetGet('name'), $this->createActivatePluginPayload($payload));
    }

    public function createActivatePluginPayload(Collection $payload)
    {
        $pluginPayload = [
            'name' => self::PLUGIN_NAME,
        ];

        return $pluginPayload;
    }

}
