<?php

namespace Drupal\entity_reference_override\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a entity reference override field mapper.
 *
 * @FeedsTarget(
 *   id = "entity_reference_override_feeds_target",
 *   field_types = {"entity_reference_override"}
 * )
 */
class EntityReferenceOverride extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id')
      ->addProperty('override')
      ->addProperty('override_format');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if (isset($values)) {

      return $values;
    }
    else {
      throw new EmptyFeedException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValues(array $values) {
    $return = [];
    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $return[] = $columns;
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        \Drupal::messenger()->addError($e->getMessage());
      }
    }

    return $return;
  }

}