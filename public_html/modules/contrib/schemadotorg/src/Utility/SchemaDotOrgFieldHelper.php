<?php

declare(strict_types=1);

namespace Drupal\schemadotorg\Utility;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Helper class Schema.org field methods.
 */
class SchemaDotOrgFieldHelper {

  /**
   * Get the Schema.org mapping for a field item or items.
   *
   * @param \Drupal\Core\Field\FieldItemInterface|\Drupal\Core\Field\FieldItemListInterface $item
   *   Field item or items.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   The Schema.org mapping for a field item or items.
   */
  public static function getSchemaMapping(FieldItemInterface|FieldItemListInterface $item): ?SchemaDotOrgMappingInterface {
    return SchemaDotOrgMapping::loadByEntity($item->getEntity());
  }

  /**
   * Get the Schema.org type for a field item or items.
   *
   * @param \Drupal\Core\Field\FieldItemInterface|\Drupal\Core\Field\FieldItemListInterface $item
   *   Field item or items.
   *
   * @return string|null
   *   The Schema.org type for a field item or items.
   */
  public static function getSchemaType(FieldItemInterface|FieldItemListInterface $item): ?string {
    $mapping = static::getSchemaMapping($item);
    return $mapping ? $mapping->getSchemaType() : NULL;
  }

  /**
   * Get the Schema.org property for a field item or items.
   *
   * @param \Drupal\Core\Field\FieldItemInterface|\Drupal\Core\Field\FieldItemListInterface $item
   *   Field item or items.
   *
   * @return string|null
   *   The Schema.org property for a field item or items.
   */
  public static function getSchemaProperty(FieldItemInterface|FieldItemListInterface $item): ?string {
    $mapping = static::getSchemaMapping($item);
    $field_name = $item->getFieldDefinition()->getName();
    return $mapping ? $mapping->getSchemaPropertyMapping($field_name) : NULL;
  }

}
