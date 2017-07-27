<?php

namespace KWRI\Kong\RoutePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RequestTransformer implements BehaviorInterface
{
    const PLUGIN_NAME = 'request-transformer';

    public function transformPayload(Collection $payload)
    {
        $params = $this->findParams($payload);

        if (!empty ($params)) {
            $this->mutateUris($payload, $params);
            $this->mutateUpstreamUrl($payload, $params);
        }

        return $payload;
    }

    private function findParams(Collection $payload)
    {
        $uri = $payload->offsetGet('uris');
        $matches = [];
        preg_match_all('#\{(.*?)\}#', $uri, $matches);
        if (empty($matches)) {
            return [];
        }
        $originalParams = current($matches);
        $contentParams = last($matches);
        $params = [];
        foreach ($contentParams as $key => $content) {
            preg_match('#.+?(?=:)#', $content, $matches);
            $name = current($matches);
            $params['original'][] = $originalParams[$key];
            if (!$name) {
                $name = $content;
                $regex = Str::contains($name, 'id') ? '\d+' : '\w+';
                $params['uris'][] = "(?<{$name}>{$regex})";
                $params['upstream'][] = "$(uri_captures.{$name})";
                continue;
            }
            $regex = Str::replaceFirst($name . ':', '', $content);
            $params['uris'][] = "(?<{$name}>{$regex})";
            $params['upstream'][] ="$(uri_captures.{$name})";
        }

        return $params;
    }

    public function mutateUris(Collection $payload, $params)
    {
        $uris = $payload->offsetGet('uris');
        $uris = str_replace($params['original'], $params['uris'], $uris);
        $lastParam = last($params['uris']);
        $isLast = substr($uris, strpos($uris, $lastParam)) == $lastParam;
        $hasLastQualifier = strpos($uris, '$') === false;

        if ($isLast && $hasLastQualifier) {
            $replacement = substr_replace($uris, '', strpos($uris, $lastParam));
            $uris = $replacement . str_replace(')', '$)', $lastParam);
        }

        $payload->offsetSet('uris', $uris);
    }

    public function mutateUpstreamUrl(Collection $payload, $params)
    {
        // mutate upstreamUrl
        $upstreamUrl = $payload->offsetGet('upstream_url');
        $upstreamUrl = str_replace($params['original'], $params['upstream'], $upstreamUrl);
        $payload->offsetSet('upstream_url', $upstreamUrl);
    }

    public function activatePlugin(KongClient $client, Collection $payload, $response = null)
    {
        if (!Str::contains($payload->offsetGet('uris'), '(?<')) {
            return;
        }

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
