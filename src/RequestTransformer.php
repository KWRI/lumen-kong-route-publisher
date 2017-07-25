<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illumiante\Support\Str;

class RequestTransformer implements BehaviorInterface
{
    public function transformPayload(Collection $payload)
    {
        $this->mutateUris($payload);
        return $payload;
    }

    public function mutateUris(Collection $payload)
    {
        $uri = $payload->offsetGet('uris');
        $uri = str_replace(['{', '}'], ['(?<', '>\w+)'], $uri);
        $uri = Str::
        $payload->offsetSet('uris', $uri);

    }
}
