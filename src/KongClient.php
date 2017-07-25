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


        public function addApi($payload) {

        }

        public function addApiOrReplace($payload)
        {

        }
}
