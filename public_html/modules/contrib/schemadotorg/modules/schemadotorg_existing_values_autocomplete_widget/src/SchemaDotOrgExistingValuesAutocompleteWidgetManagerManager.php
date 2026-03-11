<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_existing_values_autocomplete_widget;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The Schema.org existing values autocomplete widget manager.
 */
class SchemaDotOrgExistingValuesAutocompleteWidgetManagerManager implements SchemaDotOrgExistingValuesAutocompleteWidgetManagerInterface {

  /**
   * Constructs a SchemaDotOrgExistingValuesAutocompleteWidgetManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
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
    if ($field_storage_values['type'] !== 'string') {
      return;
    }

    if (empty($widget_id)) {
      $default_schema_properties = $this->configFactory
        ->get('schemadotorg_existing_values_autocomplete_widget.settings')
        ->get('default_schema_properties');
      if (in_array($schema_property, $default_schema_properties)) {
        $widget_id = 'existing_autocomplete_field_widget';
      }
    }
  }

}
