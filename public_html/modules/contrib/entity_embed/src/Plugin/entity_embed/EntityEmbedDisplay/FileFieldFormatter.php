<?php

namespace Drupal\entity_embed\Plugin\entity_embed\EntityEmbedDisplay;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_embed\Attribute\EntityEmbedDisplay;
use Drupal\entity_embed\Plugin\Derivative\FieldFormatterDeriver;

/**
 * Entity Embed Display reusing file field formatters.
 *
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayInterface
 */
#[EntityEmbedDisplay(
  id: 'file',
  label: new TranslatableMarkup('File'),
  entity_types: ['file'],
  field_type: 'file',
  deriver: FieldFormatterDeriver::class,
  provider: 'file',
)]
class FileFieldFormatter extends EntityReferenceFieldFormatter {

  /**
   * {@inheritdoc}
   */
  public function getFieldValue() {
    $value = parent::getFieldValue();
    $value += array_intersect_key($this->getConfiguration(), ['description' => '']);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    // Add support to store file description.
    $defaults['description'] = '';
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Description is stored in the configuration since it doesn't map to an
    // actual HTML attribute.
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->getConfigurationValue('description'),
      '#description' => $this->t('The description may be used as the label of the link to the file.'),
    ];

    return $form;
  }

}
