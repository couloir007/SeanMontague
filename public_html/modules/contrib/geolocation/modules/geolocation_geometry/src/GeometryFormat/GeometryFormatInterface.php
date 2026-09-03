<?php

namespace Drupal\geolocation_geometry\GeometryFormat;

use Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface;

/**
 * Geometry format.
 */
interface GeometryFormatInterface {

  /**
   * Get geometry by text.
   *
   * @param string $text
   *   JSON.
   *
   * @return \Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface|null
   *   GeometryType.
   */
  public static function geometryByText(string $text): ?GeometryTypeInterface;

}
