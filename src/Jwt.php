<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Jwt extends AuthPlugin implements BehaviorInterface
{
    const PLUGIN_NAME = 'jwt';

    /**
     * @var string
     */
    private $anonymousId = 'undefined';


    public function __construct($anonymousId)
    {
        $this->anonymousId = $anonymousId;
    }

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
            'config.anonymous' => $this->anonymousId,
        ];

        return $pluginPayload;
    }

}
