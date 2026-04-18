<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_geolocation;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * The Schema.org geolocation JSON-LD manager interface.
 */
interface SchemaDotOrgGeolocationJsonLdManagerInterface {

  /**
   * Alter the Schema.org property JSON-LD values for an entity's field items.
   *
   * @param mixed $value
   *   Alter the Schema.org property JSON-LD value.
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The entity's field item.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Object to collect JSON-LD's bubbleable metadata.
   *
   * @see hook_schemadotorg_jsonld_schema_property_alter()
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void;

}
