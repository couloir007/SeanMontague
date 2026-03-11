<?php

namespace Drupal\entity_reference_override_revisions\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_override_revisions_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference_override_revisions"
 *   }
 * )
 */
class EntityReferenceOverrideRevisionsAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = array(
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
      '#theme_wrappers' => ['container'],
    );
    $widget['target_id'] = parent::formElement($items, $delta, $element, $form, $form_state)['target_id'];
    $widget['override'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->override : '',
      '#size' => 40,
      '#weight' => 10,
    );

    if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
      $widget['override']['#placeholder'] = $this->fieldDefinition->getSetting('override_label');
    }
    else {
      $widget['override']['#title'] = $this->fieldDefinition->getSetting('override_label');
    }

    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    foreach ($values as $key => $value) {
      if ($value['target_id']) {
        $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($value['target_id']);
        // Add the current revision ID.
        $values[$key]['target_revision_id'] = $entity->getRevisionId();
      }
      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];
      }
    }
    return $values;
  }

}
