<?php

namespace Drupal\global_volcanism\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class NoaaApiClient {

  protected ClientInterface $client;
  protected LoggerInterface $logger;
  protected string $baseUri = 'https://www.ngdc.noaa.gov';

  public function __construct(ClientInterface $client, LoggerInterface $logger) {
    $this->client = $client;
    $this->logger = $logger;
  }

  protected function get(string $endpoint, array $query = []): ?array {
    try {
      $response = $this->client->request('GET', $endpoint, [
        'base_uri' => $this->baseUri,
        'timeout'  => 30.0,
        'query'    => $query,
      ]);

      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (RequestException $e) {
      ray($e);
      $this->logger
        ->error('NOAA API request failed: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }
}
