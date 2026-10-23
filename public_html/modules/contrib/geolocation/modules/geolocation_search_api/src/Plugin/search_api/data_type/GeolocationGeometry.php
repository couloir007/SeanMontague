<?php

namespace Drupal\geolocation_search_api\Plugin\search_api\data_type;

use Drupal\geolocation_geometry\GeometryFormat\GeoJSON;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\geolocation_search_api\Attribute\GeolocationSearchApiDataType;
use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides the geometry data type.
 */
#[GeolocationSearchApiDataType(
  id: 'geolocation_geometry',
  label: new TranslatableMarkup('Geolocation Geometry'),
  description: new TranslatableMarkup('Location data type implementation'),
  prefix: 'rpt',
)]
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
