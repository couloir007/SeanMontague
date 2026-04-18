<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_cer\Entity;

use Drupal\cer\Entity\CorrespondingReference;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines a corresponding reference entity.
 *
 * @ConfigEntityType(
 *   id = "corresponding_reference",
 *   label = @Translation("Corresponding reference"),
 *   handlers = {
 *     "list_builder" = "Drupal\cer\CorrespondingReferenceListBuilder",
 *     "storage" = "Drupal\cer\CorrespondingReferenceStorage",
 *     "form" = {
 *       "add" = "Drupal\cer\Form\CorrespondingReferenceForm",
 *       "edit" = "Drupal\cer\Form\CorrespondingReferenceForm",
 *       "delete" = "Drupal\cer\Form\CorrespondingReferenceDeleteForm",
 *       "sync" = "Drupal\cer\Form\CorrespondingReferenceSyncForm",
 *     }
 *   },
 *   config_prefix = "corresponding_reference",
 *   admin_permission = "administer cer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "enabled",
 *     "first_field",
 *     "second_field",
 *     "add_direction",
 *     "bundles"
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/cer",
 *     "edit-form" = "/admin/config/content/cer/{corresponding_reference}",
 *     "delete-form" = "/admin/config/content/cer/{corresponding_reference}/delete",
 *     "sync-form" = "/admin/config/content/cer/{corresponding_reference}/sync"
 *   }
 * )
 */
class SchemaDotOrgCorrespondingReference extends CorrespondingReference {

  /**
   * {@inheritdoc}
   */
  protected function calculateDifferences(FieldableEntityInterface $entity, $fieldName, $deleted = FALSE) {
    $this->setCustomFieldEntityReferences($entity, $fieldName);
    return parent::calculateDifferences($entity, $fieldName, $deleted);
  }

  /**
   * Sets custom field entity references for a given entity and field name.
   *
   * If a custom field has a target_id entity reference field, we need to load
   * the entity from the database and set the $item->entity property.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to update the field references for.
   * @param string $fieldName
   *   The name of the field to process.
   */
  protected function setCustomFieldEntityReferences(FieldableEntityInterface $entity, string $fieldName): void {
    if (!$entity->hasField($fieldName)) {
      return;
    }

    $entityField = $entity->get($fieldName);
    $fieldDefinition = $entityField->getFieldDefinition();
    $settings = $fieldDefinition->getSettings();
    if ($fieldDefinition->getType() !== 'custom') {
      return;
    }

    $type = NestedArray::getValue($settings, ['columns', 'target_id', 'type']);
    if ($type !== 'entity_reference') {
      return;
    }

    $target_type = NestedArray::getValue($settings, ['columns', 'target_id', 'target_type']);
    // Set the target type for the field because it is required to sync fields.
    // \Drupal\cer\Entity\CorrespondingReference::synchronizeCorrespondingField.
    // @phpstan-ignore-next-line
    $fieldDefinition->setSetting('target_type', $target_type);

    /** @var \Drupal\Core\Field\FieldItemInterface $fieldItem */
    foreach ($entityField as $fieldItem) {
      if (isset($fieldItem->target_id) && !isset($fieldItem->entity)) {
        $fieldItem->entity = \Drupal::entityTypeManager()
          ->getStorage($target_type)
          ->load($fieldItem->target_id);
      }
    }
  }

}
