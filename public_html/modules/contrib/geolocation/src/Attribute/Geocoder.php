<?php

namespace Drupal\geolocation\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a geocoder Attribute object.
 *
 * @see \Drupal\geolocation\GeocoderManager
 * @see plugin_api
 *
 * @Attribute
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Geocoder extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $name = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?bool $locationCapable = NULL,
    public readonly ?bool $boundaryCapable = NULL,
    public readonly ?bool $frontendCapable = NULL,
    public readonly ?bool $reverseCapable = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
