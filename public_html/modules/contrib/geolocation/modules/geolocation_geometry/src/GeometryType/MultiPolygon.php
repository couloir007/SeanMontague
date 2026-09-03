<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Multi polygon.
 *
 * @property \Drupal\geolocation_geometry\GeometryType\Polygon[] $components
 */
class MultiPolygon extends Collection {

  /**
   * Constructor.
   */
  public function __construct(array $components) {
    parent::__construct();

    foreach ($components as $comp) {
      if (!($comp instanceof Polygon)) {
        throw new \Exception("MultiPolygon can only contain Polygon elements");
      }
    }
    $this->components = $components;
  }

}
