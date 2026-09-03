<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Geometry interface.
 *
 * @property array $components.
 *   Components.
 */
interface GeometryTypeInterface {

  /**
   * Convert to GeoJson.
   *
   * @return string
   *   GeoJSON.
   */
  public function toGeoJSON(): string;

  /**
   * Convert to KML.
   *
   * @return string
   *   KML.
   */
  public function toKML(): string;

  /**
   * Convert to WKT.
   *
   * @return string
   *   WKT.
   */
  public function toWKT(): string;

  /**
   * Convert to GPX.
   *
   * @return string
   *   GPX
   */
  public function toGPX(?string $mode = NULL): string;

  /**
   * Geometry to compare.
   *
   * @param \Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface $geometry
   *   Geometry to compare.
   *
   * @return bool
   *   Equal or not.
   */
  public function equals(GeometryTypeInterface $geometry): bool;

}
