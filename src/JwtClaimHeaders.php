<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class JwtClaimHeaders extends AuthPlugin implements BehaviorInterface
{
    const PLUGIN_NAME = 'jwt-claim-headers';

    public function transformPayload(Collection $payload)
    {
        return $payload;
    }

    public function activatePlugin(KongClient $client, Collection $payload, $response = null)
    {
        $client->updateOrAddPlugin($payload->offsetGet('name'), $this->createActivatePluginPayload($payload));
    }

    protected function setPluginPayload(Collection $payload)
    {
        $pluginPayload = [
            'name' => self::PLUGIN_NAME,
        ];

        return $pluginPayload;
    }

}
