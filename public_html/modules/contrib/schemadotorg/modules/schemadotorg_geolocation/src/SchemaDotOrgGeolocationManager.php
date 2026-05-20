<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_geolocation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;

/**
 * The Schema.org geolocation manager.
 */
class SchemaDotOrgGeolocationManager implements SchemaDotOrgGeolocationManagerInterface {

  /**
   * Constructs a SchemaDotOrgGeolocationManager object.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgNamesInterface $schemaNames,
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
    // Make sure the field type is set to 'geolocation'.
    if ($field_storage_values['type'] !== 'geolocation') {
      return;
    }

    // Make sure the geolocation_leaflet.module is enabled.
    if (!$this->moduleHandler->moduleExists('geolocation_address')
      || !$this->moduleHandler->moduleExists('geolocation_leaflet')) {
      return;
    }

    // Make sure the address field name exists.
    $entity_type_id = $field_values['entity_type'];
    $bundle = $field_values['bundle'];

    $field_prefix = $this->schemaNames->getFieldPrefix();
    $field_name = $field_prefix . 'address';
    $field_config = $this->entityTypeManager
      ->getStorage('field_config')
      ->load("$entity_type_id.$bundle.$field_name");
    if (!$field_config) {
      return;
    }

    // Set the form widget.
    if (empty($widget_id)) {
      $widget_id = 'geolocation_leaflet';
      $widget_settings['third_party_settings']['geolocation_address'] = [
        'enable' => TRUE,
        'address_field' => 'schema_address',
        'geocoder' => 'photon',
        'sync_mode' => 'manual',
        'direction' => 'one_way',
        'button_position' => 'topleft',
        'ignore' => [
          'organization' => TRUE,
          'address-line1' => FALSE,
          'address-line2' => FALSE,
          'locality' => FALSE,
          'administrative-area' => FALSE,
          'postal-code' => FALSE,
        ],
      ];
    }

    // Set the view display.
    if (empty($formatter_id)) {
      $formatter_id = 'geolocation_map';
      $formatter_settings['map_provider_id'] = 'leaflet';
      $formatter_settings['map_provider_settings']['zoom'] = '15';
    }
  }

}
