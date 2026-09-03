<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Collection geometry type.
 */
abstract class Collection extends GeometryTypeBase {

  /**
   * Get property.
   *
   * @param string $property
   *   Property name.
   *
   * @return GeometryTypeInterface[]
   *   Components.
   */
  public function __get(string $property): array {
    if ($property == "components") {
      return $this->components;
    }
    else {
      throw new \Exception("Undefined property");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toWKT(): string {
    $recursiveWKT = function ($geometry) use (&$recursiveWKT) {
      if ($geometry instanceof Point) {
        return $geometry->getLongitude() . ' ' . $geometry->getLatitude();
      }
      else {
        return "(" . implode(',', array_map($recursiveWKT, $geometry->components)) . ")";
      }
    };
    return strtoupper($this->type) . call_user_func($recursiveWKT, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function toGeoJSON(): string {
    $recurviseJSON = function ($geometry) use (&$recurviseJSON) {
      if ($geometry instanceof Point) {
        return [$geometry->getLongitude(), $geometry->getLatitude()];
      }
      else {
        return array_map($recurviseJSON, $geometry->components);
      }
    };

    $value = (object) [
      'type' => $this->type,
      'coordinates' => call_user_func($recurviseJSON, $this),
    ];

    return json_encode($value);
  }

  /**
   * {@inheritdoc}
   */
  public function equals(GeometryTypeInterface $geometry): bool {
    throw new \Exception("Don't know how to compare these.");
  }

  /**
   * {@inheritdoc}
   */
  public function toGPX(?string $mode = NULL): string {
    throw new \Exception("GPX does not support Collections");
  }

  /**
   * {@inheritdoc}
   */
  public function toKML(): string {
    $kml = '<MultiGeometry>';
    foreach ($this->components as $component) {
      $kml .= $component->toKML();
    }
    $kml .= '</MultiGeometry>';

    return $kml;
  }

}
