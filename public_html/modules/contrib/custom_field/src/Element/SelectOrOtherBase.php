<?php

namespace Drupal\custom_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElementBase;

/**
 * Base class for select or other form elements.
 *
 * Properties:
 * - #select_type: Either 'list' for a select list and 'buttons' for radio
 *   buttons.
 * - #options: An associative array, where the keys are the returned values for
 *   each option, and the values are the options to be presented to the user.
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 * - #input_type: The element type to be used in the 'other' field.
 */
abstract class SelectOrOtherBase extends FormElementBase {

  /**
   * Adds an '- Other -' option to the selectbox.
   *
   * @param array $options
   *   The existing options.
   * @param string $other_option
   *   The option to add.
   *
   * @return array
   *   The new options.
   */
  protected static function addOtherOption(array $options, string $other_option = ''): array {
    if (empty($other_option)) {
      $other_option = t('- Other -');
    }
    $options['select_or_other'] = $other_option;

    return $options;
  }

  /**
   * Prepares an array to be used as a state in a form API #states array.
   *
   * @param string $state
   *   The state the element should have.
   * @param string $element_name
   *   The name of the element on which this state depends.
   * @param string $value_key
   *   The key used to select the property on which the state depends.
   * @param mixed $value
   *   The value a property should have to trigger the state.
   *
   * @return array
   *   An array with state information to be used in a #states array.
   */
  protected static function prepareState($state, $element_name, $value_key, $value): array {
    return [
      $state => [
        ':input[name="' . $element_name . '"]' => [$value_key => $value],
      ],
    ];
  }

  /**
   * Check whether the element is disabled.
   *
   * @param array $element
   *   The element to check for enabled state.
   *
   * @return bool
   *   Whether or not the element is disabled.
   */
  private static function elementIsDisabled(array $element): bool {
    return isset($element['#disabled']) && $element['#disabled'];
  }

  /**
   * Check whether or not the element may be accessed.
   *
   * @param array $element
   *   The element to check for access.
   *
   * @return bool
   *   Whether or not the element may be accessed.
   */
  private static function noElementAccess(array $element): bool {
    return isset($element['#access']) && !$element['#access'];
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getInfo(): array {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [[$class, 'processSelectOrOther']],
      '#select_type' => 'list',
      '#merged_values' => FALSE,
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#tree' => TRUE,
      '#input_type' => 'textfield',
    ];
  }

  /**
   * Render API callback: Expands the select_or_other element type.
   *
   * Expands the select or other element to have a 'select' and 'other' field.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array<string, mixed>
   *   The form element.
   */
  public static function processSelectOrOther(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    static::addSelectField($element);
    static::addOtherField($element);
    return $element;
  }

  /**
   * Adds the 'select' field to the element.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function addSelectField(array &$element): void {
    if (isset($element['#other_options']) && $element['#other_options'] !== '') {
      // Add "Other" to default values if "Other" was selected.
      $element['#default_value'] = "select_or_other";
    }

    $element['select'] = [
      '#default_value' => $element['#default_value'],
      '#required' => $element['#required'],
      '#options' => $element['#options'],
      '#attributes' => [
        'aria-label' => $element['#title'] ?? $element['#name'],
      ],
      '#weight' => 10,
    ];

    // #options has now been consumed by the 'select' sub-element. Remove it
    // from the outer wrapper element: since the outer element isn't
    // #type => 'select', core's generic illegal-choice validation
    // (FormValidator::performRequiredValidation) doesn't apply the
    // select-specific #empty_value carve-out to it, causing a spurious
    // "illegal choice" error whenever nothing is selected. The 'select'
    // sub-element enforces real choice validation correctly on its own.
    unset($element['#options']);

    if ($element['#other_allowed'] ?? TRUE) {
      $element['select']['#options'] = static::addOtherOption($element['select']['#options'], $element['#other_option'] ?? '');
    }
  }

  /**
   * Adds the 'other' field to the element.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function addOtherField(array &$element): void {
    $input_type = $element['#input_type'] ?? 'textfield';
    if ($element['#other_allowed'] ?? TRUE) {
      $element['other'] = [
        '#type' => $element['#input_type'] ?? 'textfield',
        '#attributes' => [
          'aria-label' => isset($element['#title']) ? $element['#title'] . ' Other' : $element['#name'] . ' Other',
        ],
        '#weight' => 20,
      ];
    }

    // Add numeric attributes.
    if ($input_type === 'number') {
      if (isset($element['#min'])) {
        $element['other']['#min'] = $element['#min'];
      }
      if (isset($element['#max'])) {
        $element['other']['#max'] = $element['#max'];
      }
      $element['other']['#step'] = $element['#step'] ?? 1;
    }

    if (!empty($element['#other_field_label'])) {
      $element['other']['#title'] = $element['#other_field_label'];
      $element['other']['#attributes']['aria-label'] = $element['#other_field_label'];
    }

    if (isset($element['#other_options'])) {
      $element['other']['#default_value'] = $element['#other_options'];
    }

    if (isset($element['#other_placeholder'])) {
      $element['other']['#attributes']['placeholder'] = $element['#other_placeholder'];
    }
  }

  /**
   * Adds a #states array to the other field to make hide/show work.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function addStatesHandling(array &$element): void {
    $element['other']['#states'] = static::prepareState('visible', $element['#name'] . '[select]', 'value', 'select_or_other');
  }

  /**
   * Adds a #ajax array to select field to make Form API ajax callbacks work.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function addAjaxHandling(array &$element): void {
    if (isset($element['#ajax'])) {
      $element['select']['#ajax'] = $element['#ajax'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (self::elementIsDisabled($element) || self::noElementAccess($element)) {
      unset($element['#value']);
      return NULL;
    }

    $value = NULL;
    if ($input !== FALSE && isset($input['select']) && $input['select'] !== '') {
      if ($input['select'] === 'select_or_other') {
        $value = $input['other'];
        // Add the other option to the available options to prevent
        // validation errors.
        $element['#options'][$input['other']] = $input['other'];
      }
      else {
        $value = $input['select'];
      }
    }

    return $value;
  }

}
