<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Trait\NumericTrait;

/**
 * Base class for numeric custom field types.
 */
class NumericTypeBase extends CustomFieldTypeBase {

  use NumericTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array &$form, FormStateInterface $form_state): array {
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getFieldSettings();
    $unsigned = $this->getSetting('unsigned');

    $element['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum'),
      '#default_value' => $settings['min'],
      '#min' => $unsigned ? 0 : NULL,
      '#description' => $this->t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
    ];

    $element['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum'),
      '#default_value' => $settings['max'],
      '#min' => $unsigned ? 0 : NULL,
      '#description' => $this->t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
    ];

    $element['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    $element['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();
    $constraints = [];
    // To prevent a PDO exception from occurring, restrict values in the range
    // allowed by databases.
    $type = $settings['type'];
    $min = $type !== 'float' ? $this->getDefaultMinValue($settings) : NULL;
    $max = $type !== 'float' ? $this->getDefaultMaxValue($settings) : NULL;

    // Handle range constraints.
    $min_set = isset($field_settings['min']) && $field_settings['min'] !== '';
    $max_set = isset($field_settings['max']) && $field_settings['max'] !== '';

    if ($min_set) {
      $min = $field_settings['min'];
    }
    if ($max_set) {
      $max = $field_settings['max'];
    }

    if (is_numeric($min)) {
      $constraints['Range']['min'] = $min;
    }
    if (is_numeric($max)) {
      $constraints['Range']['max'] = $max;
    }

    // Determine appropriate message.
    $messages = [
      'notInRangeMessage' => $this->t('%name: the value must be between %min and %max.', [
        '%name' => $settings['name'],
        '%min' => $min,
        '%max' => $max,
      ]),
      'minMessage' => $this->t('%name: the value may be no less than %min.', [
        '%name' => $settings['name'],
        '%min' => $min,
      ]),
      'maxMessage' => $this->t('%name: the value may be no greater than %max.', [
        '%name' => $settings['name'],
        '%max' => $max,
      ]),
    ];

    // Choose the message from the bounds the Range constraint ends up with,
    // not from the settings: Range refuses minMessage or maxMessage when it has
    // both bounds. Without a configured bound, Range keeps its default message.
    $message_type = NULL;
    if ($min_set || $max_set) {
      $has_min = isset($constraints['Range']['min']);
      $has_max = isset($constraints['Range']['max']);
      if ($has_min && $has_max) {
        $message_type = 'notInRangeMessage';
      }
      elseif ($has_min) {
        $message_type = 'minMessage';
      }
      elseif ($has_max) {
        $message_type = 'maxMessage';
      }
    }

    if (!is_null($message_type)) {
      $constraints['Range'][$message_type] = $messages[$message_type];
    }

    return $constraints;
  }

}
