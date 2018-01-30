<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AuthPlugin
{
    public function createActivatePluginPayload(Collection $payload)
    {
        if ($payload->has('middlewares') && strpos($payload->get('middlewares'), 'auth') !== false) {
            // Only enabled auth plugin for those that really need them
            return $this->setPluginPayload($payload);
        } else {
            return [];
        }
    }

    abstract protected function setPluginPayload(Collection $payload);
}
