<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\GeocoderCountryFormatting;

use Drupal\geolocation\Attribute\GeocoderCountryFormatting;
use Drupal\geolocation_leaflet\NominatimRoadFirstFormattingBase;

/**
 * Provides address formatting.
 */
#[GeocoderCountryFormatting(
  id: 'nominatim_it',
  countryCode: 'it',
  geocoder: 'nominatim'
)]
class Italy extends NominatimRoadFirstFormattingBase {

  /**
   * {@inheritdoc}
   */
  public function format(array $atomics): ?array {
    $address_elements = parent::format($atomics);

    if (!empty($atomics['countyCode'])) {
      $countyCode = explode("-", $atomics['countyCode']);
      $address_elements['administrativeArea'] = array_pop($countyCode);
    }

    if (!empty($atomics['village'])) {
      $address_elements['locality'] = $atomics['village'];
    }

    return $address_elements;
  }

}
