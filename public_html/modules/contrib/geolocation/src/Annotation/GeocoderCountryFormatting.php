<?php

namespace Drupal\geolocation\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GeocoderCountryFormatting annotation object.
 *
 * @deprecated in geolocation:4.0.0 and is removed from geolocation:4.0.1. Use Attribute instead.
 * @see https://www.drupal.org/project/geolocation/issues/3525013
 *
 * @see \Drupal\geolocation\GeocoderCountryFormattingManager
 * @see plugin_api
 *
 * @Annotation
 */
class GeocoderCountryFormatting extends Plugin {

  /**
   * The ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The country code.
   *
   * @var string
   */
  public string $countryCode;

  /**
   * The geocoder ID.
   *
   * @var string
   */
  public string $geocoder;

}
