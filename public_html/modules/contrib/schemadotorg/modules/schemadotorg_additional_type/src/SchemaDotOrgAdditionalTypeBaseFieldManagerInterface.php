<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Manages derived additional type base fields.
 */
interface SchemaDotOrgAdditionalTypeBaseFieldManagerInterface {

  /**
   * The derived full type field name.
   */
  const FULL_TYPE = 'schemadotorg_full_type';

  /**
   * The derived additional type field name.
   */
  const ADDITIONAL_TYPE = 'schemadotorg_additional_type';

  /**
   * Provides custom base field definitions for a content entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name.
   */
  public function baseFieldInfo(EntityTypeInterface $entity_type): array;

  /**
   * Synchronize the additional type base fields.
   *
   * @param array $current_types
   *   The currently enabled mapping types.
   * @param array $target_types
   *   The target mapping types.
   */
  public function syncBaseFields(array $current_types, array $target_types): void;

  /**
   * Act on an entity before it is created or updated to set the base field values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function presaveEntity(ContentEntityInterface $entity): void;

  /**
   * Gets allowed values for an entity type's base field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The base field name.
   *
   * @return array
   *   The allowed values for an entity type's base field.
   */
  public function getAllowedValues(string $entity_type_id, string $field_name): array;

  /**
   * Alter the table and field information from hook_views_data().
   *
   * @param array $data
   *   An array of all information about Views tables and fields, collected from
   *   hook_views_data(), passed by reference.
   */
  public function viewsDataAlter(array &$data): void;

}
