<?php
namespace KWRI\Kong\RoutePublisher;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Kong API:
 * https://getkong.org/docs/0.10.x/admin-api/
 *
 *
 */
class KongClient
{
        private $client;

        public function __construct(HttpClient $client)
        {
            $this->client = $client;
        }


        public function updateOrAddApi(array $payload)
        {
            try {
                $this->delete($payload);
            } catch (\Exception $e) {}

            $payload = $this->filterPayload($payload);

            return $this->client->put('/apis/', [
                'json' => $payload
            ]);
        }

        public function updateOrAddPlugin($name, array $payload)
        {
            // Nothing need to happen
            if (empty($payload)) return 'Skipped';

            $payload = $this->filterPayload($payload);

            return $this->client->put("/apis/{$name}/plugins/", [
                'json' => $payload
            ]);
        }

        public function getUpdateOrAddPluginParams($name, array $payload)
        {
            // Nothing need to happen
            if (empty($payload)) return 'Skipped';

            $payload = $this->filterPayload($payload);

            return [
                'method' => 'put',
                'uri' => "/apis/{$name}/plugins/",
                'payload' => $payload
            ];
        }

        public function delete(array $payload)
        {
            return $this->client->delete('/apis/'.$payload['name']);
        }

        public function getApiList(array $payload = [])
        {
            return $this->client->get('/apis/', [
                'query' => $payload
            ]);
        }

        /**
         * Get kong api by name.
         */
        public function getApiByName(string $name)
        {
            $apis = $this->getApiList();
            if ($apis->getStatusCode() !== 200) {
                throw new \RuntimeException("Invalid response code {$apis->getStatusCode()}");
            }
            $apis = json_decode($apis->getBody(), true);
            $apiCollection = new Collection($apis['data']);
            $apiCollection = $this->mergeWithNextApis($apis, $apiCollection);

            $matchApi = $apiCollection->filter(function ($api) use ($name) {
                return Str::startsWith($api['name'], $name);
            })->map(function($api) {
                return [
                    'id' => $api['id'],
                    'name' => $api['name'],
                    'uris' => implode(',', $api['uris'])
                ];
            });

            if ($matchApi->isEmpty()) {
                throw new \UnexpectedValueException("no routine found with name started by {$name}");
                return;
            }

            return $matchApi;
        }

        protected function filterPayload(array $payload)
        {
            // Filter out middlewares
            if (isset($payload['middlewares'])) {
                unset($payload['middlewares']);
            }

            return $payload;
        }

        /**
         * Loop until all paginated API response combined to one collection.
         */
        private function mergeWithNextApis(array $apis, Collection $apiCollection)
        {
            if (!isset($apis['next'])) {

                return $apiCollection;
            }

            $queryString = parse_url($apis['next'], PHP_URL_QUERY);
            parse_str($queryString, $queryParams);

            $apis = $this->getApiList($queryParams);
            $apis = json_decode($apis->getBody(), true);
            $apiCollection = $apiCollection->merge($apis['data']);

            return $this->mergeWithNextApis($apis, $apiCollection);
        }
}
