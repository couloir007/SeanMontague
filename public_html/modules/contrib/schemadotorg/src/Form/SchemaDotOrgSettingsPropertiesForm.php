<?php

declare(strict_types=1);

namespace Drupal\schemadotorg\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org properties settings for properties.
 */
class SchemaDotOrgSettingsPropertiesForm extends SchemaDotOrgSettingsFormBase {

  /**
   * The field type manager.
   */
  protected FieldTypePluginManagerInterface $fieldTypeManager;

  /**
   * The Schema.org schema ype manager.
   */
  protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_properties_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->fieldTypeManager = $container->get('plugin.manager.field.field_type');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $definitions = $this->fieldTypeManager->getDefinitions();
    $field_types = [];
    $field_types[] = '# Available field types';
    $field_types[] = '# ---------------------';
    foreach ($definitions as $field_type => $definition) {
      $field_types[] = '# ' . $field_type . ' = ' . $definition['label'];
    }
    $form['schema_properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Property settings'),
    ];
    $form['schema_properties']['default_fields'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Default Schema.org property fields'),
      '#rows' => 20,
      '#description' => $this->t('Enter default Schema.org property field definition used when adding a Schema.org property to an entity type.'),
      '#description_link' => 'properties',
      '#example' => implode(PHP_EOL, $field_types) . "
propertyName:
  type: string
  label: Property name
  description: Property description
  unlimited: true
  required: true
  max_length: 100
  default_value: 0
  prefix: Prefix
  suffix: Suffix
  # Field storage settings
  field_storage_settings: {  }
  # Field settings
  field_settings: {  }
  # Form display settings
  widget_id: widget_id
  widget_settings: {  }
  # View display settings
  formatter_id: formatter_id
  formatter_settings: {  }
  # Copy existing field settings.
  copy: true
  # Applicable to number fields.
  scale: 2
  min: 0
  max: 100
  # Applicable to entity reference handler settings.
  schema_types:
    SchemaType: SchemaType
  excluded_schema_types:
    SchemaType: SchemaType
  ignore_additional_mappings: true
SchemaType--propertyName:
  type: string
additional_information:
  type: text_long
  name: field_additional_information
  label: 'Additional information'
  description: 'Enter text that appears at the bottom of the page.'
",
    ];
    $form['schema_properties']['default_field_formatter_settings'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Default Schema.org property field formatter settings'),
      '#rows' => 20,
      '#description' => $this->t('Enter default Schema.org property field formatter settings used when adding a Schema.org property to an entity type.'),
      '#description_link' => 'properties',
      '#example' => '
entity_type_id:
  label: hidden
entity_type_id--bundle:
  label: hidden
SchemaType--propertyName:
  label: hidden
propertyName:
  label: hidden
',
    ];
    $form['schema_properties']['default_field_types'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Default Schema.org property field types'),
      '#description' => $this->t('Enter the field types applied to a Schema.org property when the property is added to an entity type.'),
      '#description_link' => 'properties',
      '#example' => '
schemaProperty:
  - field_type_01
  - field_type_02
  - field_type_03
SchemaType--propertyName:
  - field_type_01
  - field_type_02
  - field_type_03
',
    ];
    $form['schema_properties']['default_field_weights'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Default Schema.org property field weights'),
      '#description' => $this->t('Enter Schema.org property default field order/weight to help order fields as they are added to entity types.'),
      '#example' => '
- schemaProperty
- field_name
- entity_type--field_name
- entity_type--schemaProperty
- entity_type--bundle--field_name
- entity_type--SchemaType--schemaProperty
- SchemaType--schemaProperty',
    ];
    $unused_default_field_weights = $this->getUnusedDefaultFieldWeights();
    if ($unused_default_field_weights) {
      $form['schema_properties']['unused_default_field_weights'] = [
        '#type' => 'details',
        '#title' => $this->t('Unused default field weights'),
        '#description' => $this->t('The following default field weights are not used by any existing Schema.org mappings.'),
        'code' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#plain_text' => Yaml::encode($unused_default_field_weights),
          '#attributes' => ['data-schemadotorg-codemirror-mode' => 'text/x-yaml'],
          '#attached' => ['library' => ['schemadotorg/codemirror.yaml']],
        ],
      ];
    }
    $form['schema_properties']['disable_entity_display'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Disable entity form/view displays'),
      '#description' => $this->t('Enter the Schema.org types and properties that should NOT have entity form/view display automatically created.'),
      '#example' => '- ' . implode(PHP_EOL . '- ', $this->getEntityDisplayPatternExamples()),
    ];
    $form['schema_properties']['range_includes'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Schema.org type/property custom range includes'),
      '#description' => $this->t('Enter custom range includes for Schema.org types/properties.'),
      '#description_link' => 'types',
      '#example' => '
TypeName--propertyName:
  - Type01
  - Type02
propertyName:
  - Type01
  - Type02
',
    ];
    $form['schema_properties']['ignored_properties'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Ignored Schema.org properties'),
      '#description' => $this->t('Enter Schema.org properties that should ignored and not displayed on the Schema.org mapping form and simplifies the user experience.'),
      '#description_link' => 'properties',
      '#example' => '
- propertyName01
- propertyName02
- propertyName03
',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Retrieves the unused default field weights.
   *
   * @return array
   *   An array of unused default field weights.
   */
  protected function getUnusedDefaultFieldWeights(): array {
    $default_field_weights = $this->config('schemadotorg.settings')
      ->get('schema_properties.default_field_weights');

    // Remove common Schema.org properties.
    $default_field_weights = array_values(array_diff($default_field_weights, ['title', 'name', 'additionalType']));

    /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    $field_configs = $this->entityTypeManager->getStorage('field_config')->loadMultiple();
    foreach ($field_configs as $field_config) {
      $entity_type_id = $field_config->getTargetEntityTypeId();
      $bundle = $field_config->getTargetBundle();
      $field_name = $field_config->getName();

      $parts = [
        'entity_type_id' => $entity_type_id,
        'bundle' => $bundle,
        'field_name' => $field_name,
      ];
      $this->unsetUsedDefaultFieldWeight($default_field_weights, $parts);

      $mapping = $this->loadMapping($entity_type_id, $bundle);
      if ($mapping) {
        $this->unsetUsedDefaultFieldWeight(
          $default_field_weights,
          $parts,
          $mapping->getSchemaType(),
          $mapping->getSchemaPropertyMapping($field_name),
        );

        $additional_mappings = $mapping->getAdditionalMappings();
        foreach ($additional_mappings as $additional_mapping) {
          $this->unsetUsedDefaultFieldWeight(
            $default_field_weights,
            $parts,
            $additional_mapping['schema_type'],
            $additional_mapping['schema_properties'][$field_name] ?? NULL,
          );
        }
      }
    }

    return $default_field_weights;
  }

  /**
   * Removes a used default field weight from the list of default field weights.
   *
   * @param array &$default_field_weights
   *   An array of default field weights, passed by reference, that may be modified.
   * @param array $parts
   *   An associative array of setting name parts.
   * @param string|null $schema_type
   *   The Schema.org type.
   * @param string|null $schema_property
   *   The Schema.org property.
   */
  protected function unsetUsedDefaultFieldWeight(array &$default_field_weights, array $parts, ?string $schema_type = NULL, ?string $schema_property = NULL): void {
    $parts['schema_type'] = $schema_type;
    $parts['schema_property'] = $schema_property;

    $default_field_weight = $this->schemaTypeManager->getSetting($default_field_weights, $parts, ['multiple' => TRUE]);
    if ($default_field_weight) {
      $found_default_field_weight = array_key_first($default_field_weight);
      unset($default_field_weights[array_search($found_default_field_weight, $default_field_weights)]);
      $default_field_weights = array_values($default_field_weights);
    }
  }

  /**
   * Generates example entity display patterns.
   *
   * @return array
   *   An array of example entity display patterns.
   */
  protected function getEntityDisplayPatternExamples(): array {
    $patterns = SchemaDotOrgEntityDisplayBuilderInterface::PATTERNS;
    $examples = [];
    foreach ($patterns as $pattern) {
      $example = implode('--', $pattern);
      $example = str_replace('schema_type', 'SchemaType', $example);
      $example = str_replace('schema_property', 'schemaProperty', $example);
      $examples[] = $example;
    }
    return $examples;
  }

}
