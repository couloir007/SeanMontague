<?php

namespace Drupal\geolocation_search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides the location data type.
 *
 * @SearchApiDataType(
 *   id = "geolocation_coordinates",
 *   label = @Translation("Geolocation Coordinates"),
 *   description = @Translation("Location data type implementation"),
 *   prefix = "loc"
 * )
 */
class GeolocationCoordinates extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    if ($geojson = json_decode($value, TRUE)) {
      if ($geojson['type'] === 'Point') {
        return $geojson['coordinates'][1] . ',' . $geojson['coordinates'][0];
      }

      return NULL;
    }

    $parts = explode(',', $value);

    if (count($parts) !== 2) {
      return NULL;
    }

    if (
      !is_numeric($parts[0])
      || !is_numeric($parts[1])
    ) {
      return NULL;
    }

    return (float) $parts[0] . ',' . (float) $parts[1];

  }

}
