<?php

namespace Drupal\geolocation_maplibre\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a BaseMap Attribute object.
 *
 * @see \Drupal\geolocation_maplibre\BaseMapManager
 * @see plugin_api
 *
 * @Attribute
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class BaseMap extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $name = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
