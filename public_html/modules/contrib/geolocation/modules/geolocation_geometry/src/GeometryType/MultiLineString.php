<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Multi line string.
 */
class MultiLineString extends Collection {

  /**
   * Constructor.
   */
  public function __construct(array $components) {
    parent::__construct();

    foreach ($components as $comp) {
      if (!($comp instanceof LineString)) {
        throw new \Exception("MultiLineString can only contain LineString elements");
      }
    }
    $this->components = $components;
  }

}
