<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_smart_date;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * The Schema.org smart date manager.
 */
class SchemaDotOrgSmartDateManager implements SchemaDotOrgSmartDateManagerInterface {

  /**
   * Constructs a SchemaDotOrgSmartDateManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    public ModuleHandlerInterface $moduleHandler,
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
    // Make sure the field type is set to 'smartdate'.
    if ($field_storage_values['type'] !== 'smartdate') {
      return;
    }

    // Set field instance default value.
    $field_values['default_value'] = [
      [
        'default_duration' => 60,
        'default_duration_increments' => "30\r\n60|1 hour\r\n90\r\n120|2 hours\r\ncustom",
        'default_date_type' => 'next_hour',
        'default_date' => '',
        'min' => '',
        'max' => '',
      ],
    ];

    // If the 'Smart Date Recurring' module is installed, use for the field and
    // formatter.
    if (empty($formatter_id) && $this->moduleHandler->moduleExists('smart_date_recur')) {
      $field_values['third_party_settings'] = [
        'smart_date_recur' => [
          'allow_recurring' => TRUE,
          'month_limit' => 12,
        ],
      ];
      $formatter_id = 'smartdate_recurring';
      $formatter_settings = [
        'timezone_override' => '',
        'format_type' => 'medium',
        'format' => 'default',
        'force_chronological' => FALSE,
        'add_classes' => FALSE,
        'time_wrapper' => TRUE,
      ];
    }
  }

}
