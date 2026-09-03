<?php

namespace Drupal\geolocation\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a LocationInput Attribute object.
 *
 * @see \Drupal\geolocation\LocationInputManager
 * @see plugin_api
 *
 * @Attribute
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class LocationInput extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $name = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
