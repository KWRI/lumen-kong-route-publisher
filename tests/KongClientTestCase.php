<?php


use KWRI\Kong\RoutePublisher\KongClient;
use GuzzleHttp\Client as HttpClient;

class KongClientTestCase
{
    private $client;

    public function setUp()
    {
        parent::setUp();
        $guzzle = new HttpClient;
        $this->client = new KongClient($guzzle);
    }


    public function testPost()
    {
        $this->client->post('');
    }
}
