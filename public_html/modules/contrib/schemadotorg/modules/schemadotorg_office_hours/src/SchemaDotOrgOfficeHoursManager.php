<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_office_hours;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * The Schema.org office hours manager.
 */
class SchemaDotOrgOfficeHoursManager implements SchemaDotOrgOfficeHoursManagerInterface {

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
    // Make sure the field type is set to 'office_hours'.
    if ($field_storage_values['type'] !== 'office_hours') {
      return;
    }

    // Office hours must be unlimited.
    $field_storage_values['cardinality'] = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    // Set default settings.
    $field_storage_values['settings'] = [
      'time_format' => 'g',
      'element_type' => 'office_hours_datetime',
      'increment' => 30,
      'required_start' => FALSE,
      'limit_start' => '',
      'required_end' => FALSE,
      'limit_end' => '',
      'comment' => 1,
      'valhrs' => FALSE,
      'cardinality_per_day' => 2,
    ];
  }

}
