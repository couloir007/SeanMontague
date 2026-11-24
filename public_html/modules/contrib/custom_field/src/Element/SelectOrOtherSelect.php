<?php

namespace Drupal\custom_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;

/**
 * Provides a form element with a select box and other option.
 */
#[FormElement('custom_field_select_or_other_select')]
class SelectOrOtherSelect extends SelectOrOtherBase {

  /**
   * {@inheritdoc}
   */
  public static function processSelectOrOther(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $element = parent::processSelectOrOther($element, $form_state, $complete_form);

    static::setSelectType($element);
    static::addEmptyOption($element);
    static::addStatesHandling($element);
    static::addAjaxHandling($element);

    return $element;
  }

  /**
   * Sets the type of buttons to use for the select element.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function setSelectType(array &$element): void {
    $element['select']['#type'] = 'select';
  }

  /**
   * Adds an empty option to the select element if required.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function addEmptyOption(array &$element): void {
    if (!isset($element['#no_empty_option']) || !$element['#no_empty_option']) {
      if (!$element['#required'] || empty($element['#default_value'])) {
        $empty_value = $element['#empty_value'] ?? '';
        $empty_label = $element['#empty_option'] ?? t('- Select -');

        $element['select']['#empty_value'] = $empty_value;
        $element['select']['#empty_option'] = $empty_label;
      }
    }
  }

}
