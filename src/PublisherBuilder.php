<?php

namespace KWRI\Kong\RoutePublisher;

/**
 * Build publisher along with the plugins.
 */
class PublisherBuilder
{
    public function __construct(KongPublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function build(array $plugins)
    {
        // 1. Request transformer
        if ($plugins['with-request-transformer'] ?? null) {
            $this->publisher->attachBehavior(app()->make(RequestTransformer::class));
        }

        // 2. OIDC
        if ($plugins['with-oidc'] ?? null) {
            list($clientId, $clientSecret,
            $discovery, $introspectionEndpoint,
            $authMethod) = explode(';', $plugins['with-oidc']);
            $oidc = new Oidc($clientId, $clientSecret, $discovery, $introspectionEndpoint, $authMethod);
            $this->publisher->attachBehavior($oidc);
        }

        // 3. JWT
        if ($plugins['with-jwt'] ?? null) {
            $jwt = new Jwt($plugins['with-jwt']);
            $this->publisher->attachBehavior($jwt);
            $this->publisher->attachBehavior(app()->make(JwtClaimHeaders::class));
        }

        return $this->publisher;
    }
}
