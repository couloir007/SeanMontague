<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\GeocoderCountryFormatting;

use Drupal\geolocation\Attribute\GeocoderCountryFormatting;
use Drupal\geolocation_leaflet\NominatimRoadFirstFormattingBase;

/**
 * Provides address formatting.
 */
#[GeocoderCountryFormatting(
  id: 'nominatim_ch',
  countryCode: 'ch',
  geocoder: 'nominatim'
)]
class Switzerland extends NominatimRoadFirstFormattingBase {

}
