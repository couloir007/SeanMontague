<?php

namespace Drupal\geolocation_geocoder\Plugin\geolocation\GeocoderCountryFormatting;

use Drupal\geolocation\Attribute\GeocoderCountryFormatting;
use Drupal\geolocation\GeocoderCountryFormattingBase;
use Drupal\geolocation\GeocoderCountryFormattingInterface;

/**
 * Provides default address formatting.
 */
#[GeocoderCountryFormatting(id: 'geocoder_provider_standard')]
class Standard extends GeocoderCountryFormattingBase implements GeocoderCountryFormattingInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $atomics): ?array {
    $address_elements = parent::format($atomics);

    if (
      isset($atomics['houseNumber'])
      && isset($atomics['road'])
    ) {
      $address_elements['addressLine1'] = $atomics['road'] . ' ' . $atomics['houseNumber'];
    }
    elseif (isset($atomics['road'])) {
      $address_elements['addressLine1'] = $atomics['road'];
    }

    if (isset($atomics['suburb'])) {
      $address_elements['addressLine2'] = $atomics['suburb'];
    }

    if (isset($atomics['city'])) {
      $address_elements['locality'] = $atomics['city'];
    }
    elseif (isset($atomics['county'])) {
      $address_elements['locality'] = $atomics['county'];
    }

    if (isset($atomics['postcode'])) {
      $address_elements['postalCode'] = $atomics['postcode'];
    }

    if (isset($atomics['stateCode'])) {
      $address_elements['administrativeArea'] = $atomics['stateCode'];
    }
    elseif (isset($atomics['state'])) {
      $address_elements['administrativeArea'] = $atomics['state'];
    }

    if (isset($atomics['countryCode'])) {
      $address_elements['countryCode'] = $atomics['countryCode'];
    }

    return $address_elements;
  }

}
