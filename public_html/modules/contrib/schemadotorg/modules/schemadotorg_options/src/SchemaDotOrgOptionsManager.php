<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_options;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * The Schema.org options manager.
 */
final class SchemaDotOrgOptionsManager implements SchemaDotOrgOptionsManagerInterface {

  /**
   * Constructs a SchemaDotOrgOptionsManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface $schemaEntityFieldManager
   *   The Schema.org entity field manager.
   */
  public function __construct(
    public ConfigFactoryInterface $configFactory,
    public SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    public SchemaDotOrgEntityFieldManagerInterface $schemaEntityFieldManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function propertyFieldTypeAlter(array &$field_types, string $entity_type_id, string $schema_type, string $schema_property): void {
    // Set default field type for Schema.org types/properties with allowed values.
    $schema_property_allowed_values = $this->configFactory
      ->get('schemadotorg_options.settings')
      ->get('schema_property_allowed_values');
    $parts = [
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
    ];
    if ($this->schemaTypeManager->getSetting($schema_property_allowed_values, $parts)) {
      $field_types = ['list_string' => 'list_string'] + $field_types;
      return;
    }

    // Do not adjust the property's field type if a default field type is defined.
    $default_field = $this->schemaEntityFieldManager->getPropertyDefaultField($entity_type_id, $schema_type, $schema_property);
    if (!empty($default_field['type'])) {
      return;
    }

    $range_includes = $this->schemaTypeManager->getPropertyRangeIncludes($schema_property);
    foreach ($range_includes as $range_include) {
      // Set default field type to list string for allowed values function.
      $allowed_values_function = 'schemadotorg_options_allowed_values_' . strtolower($range_include);
      if (function_exists($allowed_values_function)) {
        $field_types = ['list_string' => 'list_string'] + $field_types;
        return;
      }

      // Set default field type to list string for enumerations.
      if ($this->schemaTypeManager->isEnumerationType($range_include)) {
        $field_types = ['list_string' => 'list_string'] + $field_types;
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function propertyFieldAlter(
    string $schema_type,
    string $schema_property,
    array &$field_storage_values,
    array &$field_values,
    ?string &$widget_id,
    array &$widget_settings,
    ?string &$formatter_id,
    array &$formatter_settings,
  ): void {
    // Only alter 'list_string' fields without allowed values.
    if ($field_storage_values['type'] !== 'list_string'
      || !empty($field_storage_values['settings']['allowed_values'])
      || !empty($field_storage_values['settings']['allowed_values_function'])) {
      return;
    }

    // Set allowed values based on the Schema.org property.
    $schema_property_allowed_values_settings = $this->configFactory
      ->get('schemadotorg_options.settings')
      ->get('schema_property_allowed_values');
    $parts = [
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
    ];
    $schema_property_allowed_values = $this->schemaTypeManager->getSetting($schema_property_allowed_values_settings, $parts);
    if ($schema_property_allowed_values) {
      $field_storage_values['settings'] = [
        'allowed_values' => $schema_property_allowed_values,
        'allowed_values_function' => '',
      ];
      return;
    }

    $property_definition = $this->schemaTypeManager->getProperty($schema_property);
    if (!$property_definition) {
      return;
    }

    // Set allowed values based the Schema.org types range includes.
    $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);

    // Set allowed values function if it exists.
    // @see schemadotorg_options_allowed_values_country()
    // @see schemadotorg_options_allowed_values_language()
    foreach ($range_includes as $range_include) {
      $allowed_values_function = 'schemadotorg_options_allowed_values_' . strtolower($range_include);
      if (function_exists($allowed_values_function)) {
        $field_storage_values['settings'] = [
          'allowed_values' => [],
          'allowed_values_function' => $allowed_values_function,
        ];
        return;
      }
    }

    // Set allowed values from all range includes that are enumerations.
    $allowed_values = [];
    foreach ($range_includes as $range_include) {
      if ($this->schemaTypeManager->isEnumerationType($range_include)) {
        $allowed_values += $this->schemaTypeManager->getTypeChildrenAsOptions($range_include);
      }
    }

    // Remove allowed values prefixes and suffixes based on the Schema.org property.
    $schema_property_allowed_values_suffix = $this->configFactory
      ->get('schemadotorg_options.settings')
      ->get("schema_property_allowed_values_remove.$schema_property");
    if ($schema_property_allowed_values_suffix) {
      foreach ($allowed_values as $value => $text) {
        $text = preg_replace('#(^' . $schema_property_allowed_values_suffix . ' | ' . $schema_property_allowed_values_suffix . '$)#', '', $text);
        $allowed_values[$value] = $text;
      }
    }

    if (!empty($allowed_values)) {
      $field_storage_values['settings'] = [
        'allowed_values' => $allowed_values,
        'allowed_values_function' => '',
      ];
    }
  }

}
