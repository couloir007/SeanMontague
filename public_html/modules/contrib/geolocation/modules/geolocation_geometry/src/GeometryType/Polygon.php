<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Polygon geometry type.
 *
 * @property \Drupal\geolocation_geometry\GeometryType\LineString[] $components
 */
class Polygon extends Collection {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $components) {
    parent::__construct();

    $outer = $components[0];

    foreach (array_slice($components, 1) as $inner) {
      if (!$outer->contains($inner)) {
        throw new \Exception("Polygon inner rings must be enclosed in outer ring");
      }
    }

    foreach ($components as $comp) {
      if (!($comp instanceof LinearRing)) {
        throw new \Exception("Polygon can only contain LinearRing elements");
      }
    }

    $this->components = $components;
  }

  /**
   * {@inheritdoc}
   */
  public function toKML(): string {
    $str = '<outerBoundaryIs>' . $this->components[0]->toKML() . '</outerBoundaryIs>';

    foreach ($this->components as $component) {
      $str .= '<innerBoundaryIs>' . $component->toKML() . '</innerBoundaryIs>';
    }

    return '<' . $this->type . '>' . $str . '</' . $this->type . '>';
  }

}
