<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_translation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldConfigInterface;

/**
 * Schema.org translate manager interface.
 */
interface SchemaDotOrgTranslationManagerInterface {

  /**
   * Applies translations based on the available mappings.
   *
   * Iterates through loaded mappings, applies translations by assigning
   * Schema.org types to target bundle entities, and persists the changes.
   */
  public function applyTranslations(): void;

  /**
   * Enable translation for a Schema.org mapping bundle or field when it is inserted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Schema.org mapping bundle.
   */
  public function entityInsert(EntityInterface $entity): void;

  /**
   * Enable translation for a Schema.org mapping field when a field config is saved.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field.
   */
  public function fieldConfigPresave(FieldConfigInterface $field_config): void;

}
