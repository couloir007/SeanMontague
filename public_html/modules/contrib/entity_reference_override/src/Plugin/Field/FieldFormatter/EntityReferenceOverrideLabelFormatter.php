<?php

namespace Drupal\entity_reference_override\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_override_label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_override_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entities with or a custom title."),
 *   field_types = {
 *     "entity_reference_override"
 *   }
 * )
 */
class EntityReferenceOverrideLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'override_action' => 'title',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['override_action'] = [
      '#type' => 'radios',
      '#options' => [
        'title' => $this->t('Replace the title'),
        'title-append' => $this->t('Append to the title'),
        'suffix' => $this->t('Add after title'),
        'class' => $this->t('Add link class'),
        'hide' => $this->t('Hide'),
      ],
      '#title' => $this->t('Use custom text to'),
      '#default_value' => $this->getSetting('override_action'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    switch ($this->getSetting('override_action')) {
      case 'title':
        $override = $this->t('title override');
        break;

      case 'title-append':
        $override = $this->t('title addition');
        break;

      case 'class':
        $override = $this->t('custom CSS class');
        break;

      case 'suffix':
        $override = $this->t('note after title');
        break;

      case 'hide':
        $override = $this->t('hide');
        break;

    }
    $summary[] = $this->t('Per-entity @override', ['@override' => $override]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    foreach ($elements as $delta => $entity) {
      if (!empty($values[$delta]['override'])) {
        switch ($this->getSetting('override_action')) {
          case 'title':
            $elements[$delta]['#title'] = $values[$delta]['override'];
            break;

          case 'title-append':
            $elements[$delta]['#title'] .= ' (' . $values[$delta]['override'] . ')';
            break;

          case 'class':
            $elements[$delta]['#attributes']['class'][] = $values[$delta]['override'];
            break;

          case 'suffix':
            if (!isset($elements[$delta]['#suffix'])) {
              $elements[$delta]['#suffix'] = '';
            }
            $elements[$delta]['#suffix'] .= ' (' . $values[$delta]['override'] . ')';
            break;

          case 'hide':
            break;
        }
      }
    }

    return $elements;
  }

}
