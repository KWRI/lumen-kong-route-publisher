<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illumiante\Support\Str;

class RequestTransformer implements BehaviorInterface
{
    const PLUGIN_NAME = 'request-transformer';

    public function transformPayload(Collection $payload)
    {
        $this->mutateUris($payload);
        return $payload;
    }

    public function mutateUris(Collection $payload)
    {
        $uri = $payload->offsetGet('uris');
        $uri = str_replace(['{', '}'], ['(?<', '>\d+)'], $uri);
        $payload->offsetSet('uris', $uri);
        $upstreamUrl = str_replace(['{', '}'],['$(uri_captures.', ')'],$payload->offsetGet('upstream_url'));
        $payload->offsetSet('upstream_url', $upstreamUrl);
    }

    public function activatePlugin(KongClient $client, Collection $payload, $response = null)
    {
        $client->updateOrAddPlugin($payload->offsetGet('name'), $this->createActivatePluginPayload($payload));
    }

    public function createActivatePluginPayload(Collection $payload)
    {
        $uri = parse_url($payload->offsetGet('upstream_url'));
        $uri = $uri['path'];
        $pluginPayload = [
            'name' => self::PLUGIN_NAME,
            'config.replace.uri' => $uri,
        ];

        return $pluginPayload;
    }
}
