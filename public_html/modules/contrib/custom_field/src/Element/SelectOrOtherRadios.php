<?php

namespace Drupal\custom_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;

/**
 * Provides a form element with radio buttons and an other option.
 */
#[FormElement('custom_field_select_or_other_radios')]
class SelectOrOtherRadios extends SelectOrOtherBase {

  /**
   * {@inheritdoc}
   */
  public static function processSelectOrOther(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $element = parent::processSelectOrOther($element, $form_state, $complete_form);

    static::setSelectType($element);
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
    $element['select']['#type'] = 'radios';
  }

}
