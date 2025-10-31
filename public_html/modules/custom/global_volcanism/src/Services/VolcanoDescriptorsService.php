<?php

namespace Drupal\global_volcanism\Services;

class VolcanoDescriptorsService extends NoaaApiClient {

  public function getDescriptors(): ?array {
    $endpoint = 'volcano/descriptor';
    return $this->get($endpoint);
  }

}
