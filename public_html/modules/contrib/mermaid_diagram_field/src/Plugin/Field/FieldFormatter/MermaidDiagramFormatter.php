<?php

namespace Drupal\mermaid_diagram_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Plugin implementation of the Mermaid Diagram formatter.
 */
#[FieldFormatter(
  id: 'mermaid_diagram_formatter',
  label: new TranslatableMarkup('Mermaid diagram'),
  field_types: [
    'mermaid_diagram',
  ],
)]
class MermaidDiagramFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_in_modal' => FALSE,
      'modal_link_text' => 'View diagram',
      'extra_settings' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['display_in_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display in modal'),
      '#default_value' => $this->getSetting('display_in_modal'),
      '#description' => $this->t('Show a link that opens the diagram in a modal'),
    ];

    $elements['modal_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal link text'),
      '#default_value' => $this->getSetting('modal_link_text'),
      '#maxlength' => 255,
      '#placeholder' => $this->t('View diagram'),
      '#description' => $this->t('Text for the modal open link'),
      // Only show this when "Display in modal" is checked.
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][display_in_modal]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['extra_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Extra configuration (JSON)'),
      '#description' => $this->t('Mermaid settings like theme can go here. Enter valid JSON. This will be passed directly to the library init function.'),
      '#default_value' => $this->getSetting('extra_settings'),
      '#rows' => 4,
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function validateSettingsForm(array &$form, FormStateInterface $form_state) {
    $json = $form_state->getValue('extra_settings');

    if (!empty($json)) {
      json_decode($json);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName(
          'extra_settings',
          t('Invalid JSON: @msg', ['@msg' => json_last_error_msg()])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Display: @mode', [
      '@mode' => $this->getSetting('display_in_modal') ? $this->t('Modal') : $this->t('Inline'),
    ]);
    if ($this->getSetting('display_in_modal')) {
      $summary[] = $this->t('Modal link text: @text', [
        '@text' => $this->getSetting('modal_link_text') ?: $this->t('View diagram'),
      ]);
    }
    $summary[] = $this->t('Extra settings: @value', [
      '@value' => $this->getSetting('extra_settings') ?: $this->t('None'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $as_modal = $this->getSetting('display_in_modal');
    $link_text = $this->getSetting('modal_link_text');
    $extra_settings = $this->getSetting('extra_settings');

    if ($items->isEmpty()) {
      return $elements;
    }

    // Base libraries for inline render.
    $base_libs = ['mermaid_diagram_field/diagram'];

    foreach ($items as $delta => $item) {
      if ($as_modal) {
        $url = Url::fromRoute('mermaid_diagram_field.modal', [
          'entity_type' => $items->getEntity()->getEntityTypeId(),
          'entity_id'   => $items->getEntity()->id(),
          'field_name'  => $items->getName(),
          'delta'       => $delta,
        ]);

        $elements[$delta] = [
          '#type' => 'link',
          '#title' => "{$link_text}: $item->title",
          '#url' => $url,
          '#attributes' => [
            'class' => ['use-ajax', 'mermaid-diagram-open'],
            'aria-haspopup' => 'dialog',
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => '90%']),
          ],
          '#attached' => [
            'library' => ['core/drupal.dialog.ajax'],
          ],
        ];
      }
      else {
        $entity = $items->getEntity();
        $elements[$delta] = [
          '#theme' => 'mermaid_diagram',
          '#mermaid' => $item->diagram,
          '#title' => $item->title,
          '#caption' => $item->caption,
          '#key' => $item->key,
          '#show_code' => $item->show_code,
          '#allow_download' => $item->allow_download,
          '#field_name' => $items->getName(),
          '#entity_type' => $entity->getEntityTypeId(),
          '#bundle' => $entity->bundle(),
          '#attached' => [
            'library' => $base_libs,
          ],
        ];
      }
    }

    $json = $this->getSetting('extra_settings');
    $config = $json ? json_decode($json, TRUE) : [$extra_settings];
    $required = [
      'startOnLoad' => FALSE,
    ];
    $final_config = array_replace_recursive($config, $required);

    $elements['#attached']['drupalSettings']['mermaidDiagramField']['extraSettings'] = $final_config;
    // @phpstan-ignore return.type
    return $elements;
  }

}
