<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Trait\NumericTrait;

/**
 * Plugin implementation of the 'select_or_other' widget.
 */
#[CustomFieldWidget(
  id: 'select_or_other',
  label: new TranslatableMarkup('Select or Other'),
  category: new TranslatableMarkup('Lists'),
  field_types: [
    'string',
    'integer',
    'float',
  ],
)]
class SelectOrOtherWidget extends ListWidgetBase {

  use NumericTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'select_element_type' => 'list',
      'other_field_label' => 'Other',
      'other_placeholder' => '',
      'other_option' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $this->getSettings() + static::defaultSettings();

    $element['select_element_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Element type'),
      '#options' => [
        'list' => $this->t('Select list'),
        'buttons' => $this->t('Radio buttons'),
      ],
      '#default_value' => $settings['select_element_type'],
    ];
    $element['other_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other placeholder'),
      '#default_value' => $settings['other_placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['other_option'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other option'),
      '#description' => $this->t('Label of the option that the user will choose when they want to supply an other value.'),
      '#default_value' => $settings['other_option'],
    ];
    $element['other_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label of the Other field'),
      '#default_value' => $settings['other_field_label'],
      '#description' => $this->t('Label for the field in which the user will supply an other value.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $this->getSettings() + static::defaultSettings();
    $field_settings = $field->getFieldSettings();
    $data_type = $field->getDataType();
    $min = NULL;
    $max = NULL;
    $input_type = 'textfield';

    if ($data_type === 'integer' || $data_type === 'float') {
      $min = $field_settings['min'] ?? NULL;
      $max = $field_settings['max'] ?? NULL;
      $input_type = 'number';
      if (!is_numeric($min)) {
        $min = static::getDefaultMinValue($field->getSettings());
      }
      if (!is_numeric($max)) {
        $max = static::getDefaultMaxValue($field->getSettings());
      }
    }

    $default = $element['#default_value'];
    $default_key = is_scalar($default) ? (string) $default : $default;
    $is_buttons = ($settings['select_element_type'] ?? 'list') === 'buttons';
    $has_default = $default !== NULL && $default !== '';

    $options = $element['#options'] ?? [];

    // Radios has no #empty_option/#empty_value concept of its own (that's
    // handled by SelectOrOtherSelect::addEmptyOption() for the list
    // variant), so prepend an explicit empty choice here when the field
    // isn't required, mirroring the same condition used for the select
    // variant so behavior stays consistent between the two.
    if ($is_buttons && !$element['#required']) {
      $options = ['' => $settings['empty_option'] ?? $this->t('- None -')] + $options;
    }

    return [
      '#type' => $is_buttons ? 'custom_field_select_or_other_radios' : 'custom_field_select_or_other_select',
      '#options' => $options,
      '#empty_option' => $settings['empty_option'],
      '#other_placeholder' => $settings['other_placeholder'],
      '#other_option' => $settings['other_option'],
      '#other_field_label' => $settings['other_field_label'],
      // Populate the 'other' option if the value is not in the options list.
      '#other_options' => $has_default && !empty($element['#options']) && !isset($element['#options'][$default_key]) ? $default : NULL,
      '#input_type' => $input_type,
      '#min' => $min,
      '#max' => $max,
      '#step' => $data_type === 'float' ? 'any' : 1,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, array $column): mixed {
    if (!\is_array($value) || !isset($value['select']) || $value['select'] === '') {
      return NULL;
    }

    if ($value['select'] === 'select_or_other') {
      $subfield_type = $column['type'] ?? NULL;
      $other = $value['other'] ?? NULL;
      if ($other === '') {
        return NULL;
      }
      elseif (\in_array($subfield_type, ['integer', 'float']) && !\is_numeric($other)) {
        return NULL;
      }
      return $other;
    }

    return $value['select'];
  }

}
