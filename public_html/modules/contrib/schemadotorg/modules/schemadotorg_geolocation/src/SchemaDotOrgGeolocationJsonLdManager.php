<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_geolocation;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * The Schema.org geolocation JSON-LD manager.
 */
class SchemaDotOrgGeolocationJsonLdManager implements SchemaDotOrgGeolocationJsonLdManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void {
    $field_type = $item->getFieldDefinition()->getType();
    if ($field_type !== 'geolocation') {
      return;
    }

    $value = [
      '@type' => 'GeoCoordinates',
      'latitude' => $item->lat,
      'longitude' => $item->lng,
    ];
  }

}
