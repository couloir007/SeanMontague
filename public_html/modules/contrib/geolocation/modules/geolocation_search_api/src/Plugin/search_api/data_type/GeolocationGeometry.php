<?php

namespace Drupal\geolocation_search_api\Plugin\search_api\data_type;

use Drupal\geolocation_geometry\GeometryFormat\GeoJSON;
use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides the location data type.
 *
 * @SearchApiDataType(
 *   id = "geolocation_geometry",
 *   label = @Translation("Geolocation Geometry"),
 *   description = @Translation("Location data type implementation"),
 *   prefix = "rpt"
 * )
 */
class GeolocationGeometry extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    if (json_decode($value)) {
      if ($geometry = GeoJSON::geometryByText($value)) {
        return $geometry->toWKT();
      }
    }
    return NULL;
  }

}
