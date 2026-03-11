<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_allowed_formats;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * The Schema.org allowed Formats manager.
 */
class SchemaDotOrgAllowedFormatsManager implements SchemaDotOrgAllowedFormatsManagerInterface {

  /**
   * Constructs a SchemaDotOrgAllowedFormatsManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

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
    if (!in_array($field_storage_values['type'], _allowed_formats_field_types())) {
      return;
    }

    $config = $this->configFactory->get('schemadotorg_allowed_formats.settings');

    // Set default allowed formats.
    $default_allowed_formats = $config->get('default_allowed_formats');
    $parts = [
      'entity_type_id' => $field_values['entity_type'],
      'bundle' => $field_values['bundle'],
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
    ];
    $allowed_formats = $this->schemaTypeManager->getSetting($default_allowed_formats, $parts);
    if ($allowed_formats) {
      $field_values['settings']['allowed_formats'] = $allowed_formats;
    }

    // Set default hide help.
    $default_hide_help = $config->get('default_hide_help');
    if ($default_hide_help) {
      $widget_settings['third_party_settings']['allowed_formats']['hide_help'] = '1';
    }

    // Set default hide guidelines.
    $default_hide_guidelines = $config->get('default_hide_guidelines');
    if ($default_hide_guidelines) {
      $widget_settings['third_party_settings']['allowed_formats']['hide_guidelines'] = '1';
    }
  }

}
