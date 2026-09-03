<?php

namespace Drupal\geolocation_geometry\Plugin\migrate\process;

use Drupal\geolocation_geometry\GeometryFormat\WKT;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin converts latitude and latitude to a GeoJSON Point.
 *
 * @MigrateProcessPlugin(
 *   id = "geolocation_wkt_to_geometry",
 * )
 */
class WktToGeometry extends ProcessPluginBase implements MigrateProcessInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return '';
    }

    $geometry = WKT::geometryByText($value);

    return match ($this->configuration['format'] ?? 'geojson') {
      'geojson' => $geometry->toGeoJSON(),
      'wkt' => $geometry->toWKT(),
      'kml' => $geometry->toKML(),
      'gpx' => $geometry->toGPX(),
      default => '',
    };
  }

}
