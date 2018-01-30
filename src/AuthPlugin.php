<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AuthPlugin
{
    public function createActivatePluginPayload(Collection $payload)
    {
        if ($payload->has('middlewares') && in_array('auth', $payload->get('middlewares'))) {
            // Only enabled auth plugin for those that really need them
            $this->setPluginPayload($payload);
        } else {
            return [];
        }
    }

    abstract protected function setPluginPayload(Collection $payload);
}
