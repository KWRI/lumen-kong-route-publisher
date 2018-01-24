<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Oidc implements BehaviorInterface
{
    const PLUGIN_NAME = 'oidc';

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $discovery;

    /**
     * @var string
     */
    private $introspectionEndpoint;

    /**
     * @var string
     */
    private $authMethod;

    public function __construct($clientId, $clientSecret, $discovery, $introspectionEndpoint, $authMethod)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->discovery = $discovery;
        $this->introspectionEndpoint = $introspectionEndpoint;
        $this->authMethod = $authMethod;
    }

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
            'config.client_id' => $this->clientId,
            'config.client_secret' => $this->clientSecret,
            'config.discovery' => $this->discovery,
            'config.introspection_endpoint' => $this->introspectionEndpoint,
            'config.token_endpoint_auth_method' => $this->authMethod,
            // Default behaviour, can't be exposed if needed...
            'config.scope' => 'openid email profile',
            'config.response_type' => 'token',
        ];

        return $pluginPayload;
    }

}
