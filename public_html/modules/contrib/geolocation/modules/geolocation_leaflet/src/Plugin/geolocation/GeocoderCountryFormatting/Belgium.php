<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\GeocoderCountryFormatting;

use Drupal\geolocation\Attribute\GeocoderCountryFormatting;
use Drupal\geolocation_leaflet\NominatimRoadFirstFormattingBase;

/**
 * Provides address formatting.
 */
#[GeocoderCountryFormatting(
  id: 'nominatim_be',
  countryCode: 'be',
  geocoder: 'nominatim'
)]
class Belgium extends NominatimRoadFirstFormattingBase {

}
