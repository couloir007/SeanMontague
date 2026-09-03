<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\GeocoderCountryFormatting;

use Drupal\geolocation\Attribute\GeocoderCountryFormatting;
use Drupal\geolocation_leaflet\NominatimRoadFirstFormattingBase;

/**
 * Provides address formatting.
 */
#[GeocoderCountryFormatting(
  id: 'nominatim_at',
  countryCode: 'at',
  geocoder: 'nominatim'
)]
class Austria extends NominatimRoadFirstFormattingBase {

}
