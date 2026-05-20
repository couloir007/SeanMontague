<?php

namespace Drupal\entity_reference_override\Plugin\diff\Field;

use Drupal\Core\Field\FieldItemListInterface;
use const Drupal\diff\Plugin\diff\Field\COMPARE_ENTITY_REFERENCE_LABEL;
use Drupal\diff\Plugin\diff\Field\EntityReferenceFieldBuilder;

/**
 * Plugin to diff entity reference override fields.
 *
 * @FieldDiffBuilder(
 *   id = "entity_reference_override_field_diff_builder",
 *   label = @Translation("Entity Reference w/custom text Field Diff"),
 *   field_types = {
 *     "entity_reference_override"
 *   },
 * )
 */
class EntityReferenceOverrideFieldBuilder extends EntityReferenceFieldBuilder {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = [];
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        if ($field_item->entity) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          $entity = $field_item->entity;
          if ($this->configuration['compare_entity_reference'] == COMPARE_ENTITY_REFERENCE_LABEL) {
            $result[$field_key][] = $this->t('@label (Override: @override)', [
              '@label' => $entity->label(),
              '@type' => $entity->getEntityType()->getLabel(),
              '@override' => $field_item->getValue()['override'],
            ]);
          }
          else {
            $result[$field_key][] = $this->t('Entity ID: @id (Override: @override)', [
              '@label' => $entity->id(),
              '@override' => $field_item->getValue()['override'],
            ]);
          }
        }
      }
    }

    return $result;
  }

}


