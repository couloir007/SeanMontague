<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Geometry Collection.
 */
class GeometryCollection extends Collection {

  /**
   * Constructor.
   */
  public function __construct(array $components) {
    parent::__construct();

    foreach ($components as $comp) {
      if (!($comp instanceof GeometryTypeInterface)) {
        throw new \Exception("GeometryCollection can only contain Geometry elements");
      }
    }
    $this->components = $components;
  }

  /**
   * {@inheritdoc}
   */
  public function toWKT(): string {
    return strtoupper($this->type) . "(" . implode(',', array_map(function ($comp) {
      return $comp->toWKT();
    }, $this->components)) . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function toGeoJSON(): string {
    $value = (object) [
      'type' => $this->type,
      'geometries' => array_map(function ($comp) {
        // XXX: quite ugly.
        return json_decode($comp->toGeoJSON());
      }, $this->components),
    ];
    return json_encode($value);
  }

}
