<?php

namespace Drupal\entity_reference_override\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\Random;

/**
 * Plugin implementation of the 'entity_reference_override' field type.
 *
 * @FieldType(
 *   id = "entity_reference_override",
 *   label = @Translation("Entity reference w/custom text"),
 *   description = @Translation("Entity reference with custom text"),
 *   category = "reference",
 *   default_widget = "entity_reference_override_autocomplete",
 *   default_formatter = "entity_reference_override_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList" * )
 */
class EntityReferenceOverride extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $override_definition = DataDefinition::create('string')
      ->setLabel($field_definition->getSetting('override_label'))
      ->setRequired(FALSE);
    $properties['override'] = $override_definition;
    $override_format = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('override_format'))
      ->setRequired(FALSE);
    $properties['override_format'] = $override_format;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['override'] = [
      'type' => 'varchar',
      'length' => 4094,
      'not null' => FALSE,
    ];
    $schema['columns']['override_format'] = [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'override_label' => t('Custom text'),
      'override_format' => NULL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::fieldSettingsForm($form, $form_state);

    $elements['override_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom text label'),
      '#default_value' => $this->getSetting('override_label'),
      '#description' => $this->t('Also used as a placeholder in multi-value instances.'),
    ];

    // Collect and add to text formats administrators can choose for people
    // entering custom text with which to override a field or label.
    $override_formats = [];
    $override_formats[NULL] = $this->t('Single line, no markup');
    $formats = filter_formats();
    foreach ($formats as $name => $format) {
      $override_formats[$name] = $format->label();
    }
    // We should probably move the choice of what to override to here instead of
    // the widget, as it doesn't make sense to allow a class to be set to a
    // multiline, HTML-capable text format.
    $elements['override_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Custom text format'),
      '#options' => $override_formats,
      '#default_value' => $this->getSetting('override_format'),
      '#description' => $this->t('Classes and labels can only be overridden with the "@null" option.  When any other text format is chosen, editors will have a text area for entering the custom override text, with any WYSIWYG editor that is configured for that format.  This is mostly useful when overriding fields whose input is also a text area.', ['@null' => $override_formats[NULL]]),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    // In the base EntityReference class, this is used to populate the
    // list of field-types with options for each destination entity type.
    // Too much work, we'll just make people fill that out later.
    // Also, keeps the field type dropdown from getting too cluttered.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Entity reference base will handle populating the target_id.
    $values = parent::generateSampleValue($field_definition);
    $random = new Random();
    $values['override'] = $random->word(mt_rand(1, 255));
    return $values;
  }

}
