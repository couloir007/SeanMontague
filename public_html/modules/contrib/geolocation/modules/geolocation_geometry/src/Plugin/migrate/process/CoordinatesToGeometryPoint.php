<?php

namespace Drupal\geolocation_geometry\Plugin\migrate\process;

use Drupal\geolocation_geometry\GeometryType\Point;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin converts latitude and latitude to a GeoJSON Point.
 *
 * @MigrateProcessPlugin(
 *   id = "geolocation_coordinates_to_geometry_point",
 * )
 */
class CoordinatesToGeometryPoint extends ProcessPluginBase implements MigrateProcessInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (
      empty($value)
      || is_null($value[0])
      || $value[0] === ''
      || is_null($value[1])
      || $value[1] === ''
    ) {
      return '';
    }

    $point = new Point([$value[1], $value[0]]);

    return match ($this->configuration['format'] ?? 'geojson') {
      'geojson' => $point->toGeoJSON(),
      'wkt' => $point->toWKT(),
      'kml' => $point->toKML(),
      'gpx' => $point->toGPX(),
      default => '',
    };
  }

}
