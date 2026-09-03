<?php

namespace Drupal\geolocation\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a MapFeature Attribute object.
 *
 * @see \Drupal\geolocation\MapFeatureManager
 * @see plugin_api
 *
 * @Attribute
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MapFeature extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $name = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $type = NULL,
    public readonly bool $hidden = FALSE,
    public readonly ?string $deriver = NULL,
  ) {}

}
