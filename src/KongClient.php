<?php
namespace KWRI\Kong\RoutePublisher;

use GuzzleHttp\Client as HttpClient;
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

            return $this->client->put('/apis/', [
                'json' => $payload
            ]);
        }

        public function updateOrAddPlugin($name, array $payload)
        {
            return $this->client->put("/apis/{$name}/plugins/", [
                'json' => $payload
            ]);
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
}
