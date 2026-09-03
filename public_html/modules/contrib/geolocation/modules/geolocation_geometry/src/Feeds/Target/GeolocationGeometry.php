<?php

namespace Drupal\geolocation_geometry\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a geolocation field mapper for GeoJSON sources.
 *
 * @FeedsTarget(
 *   id = "geolocation_geometry_feeds_target",
 *   field_types = {
 *     "geolocation_geometry_geometry",
 *     "geolocation_geometry_geometrycollection",
 *     "geolocation_geometry_linestring",
 *     "geolocation_geometry_multilinestring",
 *     "geolocation_geometry_multipoint",
 *     "geolocation_geometry_multipolygon",
 *     "geolocation_geometry_point",
 *     "geolocation_geometry_polygon",
 *   }
 * )
 */
class GeolocationGeometry extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values): void {
    $values['geojson'] = $values['value'];

    unset($values['value']);
    parent::prepareValue($delta, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function isTargetTranslatable(): bool {
    return FALSE;
  }

}
