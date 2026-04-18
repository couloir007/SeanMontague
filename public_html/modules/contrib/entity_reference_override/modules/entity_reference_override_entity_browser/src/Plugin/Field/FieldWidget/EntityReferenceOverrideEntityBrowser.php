<?php

namespace Drupal\entity_reference_override_entity_browser\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;

/**
 * Entity_reference_override widget that supports Entity browser.
 *
 * @FieldWidget(
 *   id = "entity_browser_entity_reference_override",
 *   label = @Translation("Entity browser"),
 *   description = @Translation("Uses entity browser to select entities."),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference_override"
 *   }
 * )
 */
class EntityReferenceOverrideEntityBrowser extends EntityReferenceBrowserWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $defaultValueMap = $this->formElementDefaultValues($items, $element, $form_state);
    $element['entity_reference_override_default_values'] = [
      '#type' => 'hidden',
      '#value' => serialize($defaultValueMap),
    ];

    foreach ($element['current']['items'] as $key => $item) {
      $element['current']['items'][$key]['override'] = [
        '#type' => 'textfield',
        '#default_value' => !empty($defaultValueMap[$key]) ? $defaultValueMap[$key]['override'] : '',
        '#size' => 40,
        '#weight' => 10,
      ];

      if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
        $element['current']['items'][$key]['override']['#placeholder'] = $this->fieldDefinition->getSetting('override_label');
      }
      else {
        $element['current']['items'][$key]['override']['#title'] = $this->fieldDefinition->getSetting('override_label');
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $massagedValues = parent::massageFormValues($values, $form, $form_state);
    if (!isset($values['current']['items'])) {
      return $massagedValues;
    }

    foreach ($values['current']['items'] as $delta => $item) {
      $massagedValues[$delta]['override'] = $item['override'];
    }
    return $massagedValues;
  }

  /**
   * {@inheritdoc}
   */
  public static function removeItemSubmit(&$form, FormStateInterface $form_state) {
    self::removeOverrideItemSubmit($form, $form_state);
    parent::removeItemSubmit($form, $form_state);
  }

  /**
   * Determines the override values used for the form element.
   *
   * This will:
   * - Return the current form values map when the submit came from the element
   *   (ajax callback).
   * - Or create the value map when we are loading the form for the first time
   *   (existing item values).
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item to extract the override values from.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The list of override values for the form element.
   */
  protected function formElementDefaultValues(FieldItemListInterface $items, array $element, FormStateInterface $form_state): array {
    if ($this->submitIsRelevant($element, $form_state)) {
      return $this->formElementsDefaultValuesByTrigger($form_state);
    }

    $defaultValueMap = [];
    foreach ($items as $key => $item) {
      $defaultValueMap[$key] = [
        'override' => $item->override,
        'target_id' => $item->target_id,
      ];
    }
    return $defaultValueMap;
  }

  /**
   * Determine if we're submitting and if submit came from this widget.
   *
   * In case there are more instances of this widget on the same page we need to
   * check if submit came from this instance.
   *
   * @param array $element
   *   The element to check for.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   Submit is relevant.
   */
  protected function submitIsRelevant(array $element, FormStateInterface $form_state): bool {
    $trigger = $form_state->getTriggeringElement();
    if (!$trigger) {
      return FALSE;
    }

    $parent = end($trigger['#parents']);
    if (!in_array($parent, ['target_id', 'remove_button'])) {
      return FALSE;
    }

    $fieldNameKey = $parent === 'target_id'
      ? 2
      : static::$deleteDepth + 1;
    $fieldNameKey = count($trigger['#parents']) - $fieldNameKey;

    return $trigger['#parents'][$fieldNameKey] === $this->fieldDefinition->getName()
      && array_slice($trigger['#parents'], 0, count($element['#field_parents'])) === $element['#field_parents'];
  }

  /**
   * Get the default values by the trigger element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The parents.
   */
  protected function formElementsDefaultValuesByTrigger(FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();

    // Submit was triggered by hidden "target_id" element when entities were
    // added via entity browser.
    if (!empty($trigger['#ajax']['event'])
      && $trigger['#ajax']['event'] === 'entity_browser_value_updated'
    ) {
      $parents = array_slice($trigger['#parents'], 0, -1);
    }
    // Submit was triggered by one of the "Remove" buttons. We need to walk
    // few levels up to read value of "target_id" element.
    elseif ($trigger['#type'] === 'submit'
      && strpos($trigger['#name'], $this->fieldDefinition->getName() . '_remove_') === 0
    ) {
      $parents = array_slice($trigger['#parents'], 0, -static::$deleteDepth);
    }

    if (!isset($parents)) {
      return [];
    }

    $value = $form_state->getValue($parents);
    return empty($value['entity_reference_override_default_values'])
      ? []
      : unserialize($value['entity_reference_override_default_values'], ['allowed_classes' => FALSE]);
  }

  /**
   * Remove the override values when a referenced item is removed from element.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected static function removeOverrideItemSubmit(array &$form, FormStateInterface $form_state): void {
    $triggeringElement = $form_state->getTriggeringElement();
    if (empty($triggeringElement['#attributes']['data-entity-id'])) {
      return;
    }
    if (!isset($triggeringElement['#attributes']['data-row-id'])) {
      return;
    }

    $rowId = $triggeringElement['#attributes']['data-row-id'];
    $parents = array_slice($triggeringElement['#parents'], 0, -static::$deleteDepth);
    $arrayParents = array_slice($triggeringElement['#array_parents'], 0, -static::$deleteDepth);

    $values = $form_state->getValue($parents);
    $targetIds = [];
    preg_match_all('/media:(\d+)/', $values['target_id'], $targetIds);
    $overrides = array_column($values['current']['items'], 'override');

    // Rebuild the values without the removed item.
    $defaultValues = [];
    $currentValues = [];
    foreach ($targetIds[1] as $index => $targetId) {
      if ($index === $rowId) {
        continue;
      }

      $defaultValues[] = [
        'override' => $overrides[$index],
        'target_id' => (int) $targetId,
      ];
      $currentValues[] = ['override' => $overrides[$index]];
    }

    // Set new "entity_reference_override_default_values" value for this field.
    $value = serialize($defaultValues);
    $element = &NestedArray::getValue($form, array_merge($arrayParents, ['entity_reference_override_default_values']));
    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
    NestedArray::setValue($form_state->getUserInput(), $element['#parents'], $value);

    // Set new "current" value for this field.
    $currentElement = &NestedArray::getValue($form, array_merge($arrayParents, ['current']));
    NestedArray::setValue($form_state->getUserInput(), $currentElement['#parents'], $currentValues);
  }

}
