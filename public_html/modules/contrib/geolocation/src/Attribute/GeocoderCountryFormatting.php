<?php

namespace Drupal\geolocation\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a GeocoderCountryFormatting Attribute object.
 *
 * @see \Drupal\geolocation\GeocoderCountryFormattingManager
 * @see plugin_api
 *
 * @Attribute
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class GeocoderCountryFormatting extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?string $countryCode = NULL,
    public readonly ?string $geocoder = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
