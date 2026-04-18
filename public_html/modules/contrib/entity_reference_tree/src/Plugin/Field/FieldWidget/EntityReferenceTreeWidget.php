<?php

namespace Drupal\entity_reference_tree\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * A entity reference tree widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_tree",
 *   label = @Translation("Entity reference tree widget"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceTreeWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $arr_element = parent::formElement($items, $delta, $element, $form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'entity_reference_tree/widget';
    $arr_target = empty($arr_element['target_id']['#selection_settings']['target_bundles']) ? [] : $arr_element['target_id']['#selection_settings']['target_bundles'];
    $str_target_type = $arr_element['target_id']['#target_type'];
    // Target bundle of the entity tree.
    if (empty($arr_target)) {
      $str_target = '*';
    }
    else {
      $str_target = implode(',', $arr_target);
    }

    // The id of the autocomplete text field.
    // To ensure unqiueness when being used within Paragraph entities
    // add the ids of any parent elements as a prefix to the the
    // edit id.
    $parents = $element['#field_parents'];
    $id_prefix = '';
    if (!empty($parents)) {
      // Empty check necessary because implode will return the
      // separator when given an empty array.
      $id_prefix = str_replace('_', '-', implode('-', array_merge($parents))) . '-';
    }

    // Including the delta in the id
    // to follow the Entity Reference module's convention.
    $edit_id = 'edit-' . $id_prefix . str_replace('_', '-', $items->getName()) . '-' . $delta . '-target-id';
    $theme = $this->normalizeTheme((string) $this->getSetting('theme'));
    $dots = ((int) $this->getSetting('dots') === 1) ? 1 : 0;
    $limit = (int) $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $worker = $this->normalizeBooleanFlag($this->getSetting('worker'));
    $disable_animation = $this->normalizeBooleanFlag($this->getSetting('disable_animation'));
    $force_text = $this->normalizeBooleanFlag($this->getSetting('force_text'));

    $arr_element['target_id']['#id'] = $edit_id;
    $arr_element['target_id']['#tags'] = TRUE;
    $arr_element['target_id']['#default_value'] = $items->referencedEntities();
    $arr_element['target_id']['#maxlength'] = (int) ($this->getSetting('autocomplete_maxlength') ?? 1024);

    $label = $this->getSetting('label');
    if (!$label) {
      $label = $this->t('@label tree', [
        '@label' => ucfirst(str_replace('_', ' ', $str_target_type)),
      ]);
    }
    else {
      $label = $this->t('@label', ['@label' => $label]);
    }

    $dialog_title = $this->getSetting('dialog_title');
    if (empty($dialog_title)) {
      $dialog_title = $label;
    }
    else {
      $dialog_title = $this->t('@title', ['@title' => $dialog_title]);
    }
    $dialog_title_value = $this->normalizeDialogTitle((string) $dialog_title);

    $modal_token_value = $this->buildModalTokenValue(
      $edit_id,
      $str_target,
      $str_target_type,
      $theme,
      $dots,
      $dialog_title_value,
      $limit,
      $worker,
      $disable_animation,
      $force_text
    );
    $modal_token = \Drupal::csrfToken()->get($modal_token_value);

    $arr_element['dialog_link'] = [
      '#type' => 'link',
      '#title' => $label,
      '#url' => Url::fromRoute(
          'entity_reference_tree.widget_form',
          [
            'field_edit_id' => $edit_id,
            'bundle' => $str_target,
            'entity_type' => $str_target_type,
            'theme' => $theme,
            'dots' => $dots,
            'dialog_title' => $dialog_title_value,
            'limit' => $limit,
            'worker' => $worker,
            'disable_animation' => $disable_animation,
            'force_text' => $force_text,
            'token' => $modal_token,
          ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
      ],
    ];

    return $arr_element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // JsTree settings.
      'theme' => 'default',
      'worker' => TRUE,
      'disable_animation' => FALSE,
      'force_text' => FALSE,
        // Using dot line.
      'dots' => 0,
        // Button label.
      'label' => '',
        // Dialog title.
      'dialog_title' => '',
        // Maximum length of autocomplete.
      'autocomplete_maxlength' => '1024',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    // JsTRee theme.
    $element['theme'] = [
      '#type' => 'radios',
      '#title' => t('JsTree theme'),
      '#default_value' => $this->getSetting('theme'),
      '#required' => TRUE,
      '#options' => [
        'default' => $this
          ->t('Default'),
        'default-dark' => $this
          ->t('Default Dark'),
      ],
    ];
    $element['worker'] = [
      '#type' => 'checkbox',
      '#title' => t('Worker'),
      '#description' => t('If left as true web workers will be used to parse incoming JSON data where possible, so that the UI will not be blocked by large requests. Workers are however about 30% slower'),
      '#default_value' => $this->getSetting('worker'),
    ];
    $element['disable_animation'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable animation'),
      '#description' => t('Set this to false to disable the animation'),
      '#default_value' => $this->getSetting('disable_animation'),
    ];
    $element['force_text'] = [
      '#type' => 'checkbox',
      '#title' => t('Force text'),
      '#description' => t('Force node text to plain text (and escape HTML)'),
      '#default_value' => $this->getSetting('force_text'),
    ];
    // Tree dot.
    $element['dots'] = [
      '#type' => 'radios',
      '#title' => t('Dot line'),
      '#default_value' => $this->getSetting('dots'),
      '#options' => [
        0 => $this
          ->t('No'),
        1 => $this
          ->t('Yes'),
      ],
    ];
    // Button label.
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#default_value' => $this->getSetting('label'),
    ];

    $element['dialog_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog title'),
      '#default_value' => $this->getSetting('dialog_title'),
    ];

    $element['autocomplete_maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete field maximum length'),
      '#description' => $this->t('Use this for fields which can accept a larger number of taxonomy terms.'),
      '#default_value' => $this->getSetting('autocomplete_maxlength'),
      '#min' => 1024,
      '#step' => 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // JsTree theme.
    $summary[] = t('JsTree theme: @theme', ['@theme' => $this->getSetting('theme')]);
    // Button label.
    if ($label = $this->getSetting('label')) {
      $summary[] = t('Button label: @label', ['@label' => $label]);
    }
    // Dialog title.
    if ($label = $this->getSetting('dialog_title')) {
      $summary[] = t('Dialog title: @title', ['@title' => $label]);
    }

    return $summary;
  }

  /**
   * Build the CSRF token value for the modal endpoint.
   */
  private function buildModalTokenValue(
    string $field_edit_id,
    string $bundle,
    string $entity_type,
    string $theme,
    int $dots,
    string $dialog_title,
    int $limit,
    int $worker,
    int $disable_animation,
    int $force_text
  ): string {
    return implode(':', [
      $field_edit_id,
      $bundle,
      $entity_type,
      $theme,
      $dots,
      $dialog_title,
      $limit,
      $worker,
      $disable_animation,
      $force_text,
    ]);
  }

  /**
   * Normalize checkbox/radio values to 0/1.
   */
  private function normalizeBooleanFlag($value): int {
    if (is_bool($value)) {
      return (int) $value;
    }

    if (is_int($value)) {
      return $value === 1 ? 1 : 0;
    }

    if (is_string($value)) {
      $normalized = strtolower($value);
      if (in_array($normalized, ['1', 'true', 'on', 'yes'], TRUE)) {
        return 1;
      }
    }

    return 0;
  }

  /**
   * Restrict jsTree themes to known values.
   */
  private function normalizeTheme(string $theme): string {
    return in_array($theme, ['default', 'default-dark'], TRUE) ? $theme : 'default';
  }

  /**
   * Normalize dialog title to a plain, route-safe value.
   */
  private function normalizeDialogTitle(string $dialog_title): string {
    $plain_text = trim(strip_tags(Html::decodeEntities($dialog_title)));
    if ($plain_text === '') {
      return 'Entity tree';
    }

    // dialog_title is a route path parameter and cannot contain '/'.
    $plain_text = str_replace(['/', '\\'], ' ', $plain_text);
    return preg_replace('/\s+/', ' ', $plain_text) ?? 'Entity tree';
  }

}
