<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * The Schema.org additional type manager.
 */
class SchemaDotOrgAdditionalTypeJsonLdManager implements SchemaDotOrgAdditionalTypeJsonLdManagerInterface {

  /**
   * Constructs a SchemaDotOrgAdditionalTypeJsonLdManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgNamesInterface $schemaNames,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaTypeEntityAlter(array &$data, EntityInterface $entity, ?SchemaDotOrgMappingInterface $mapping, ?BubbleableMetadata $bubbleable_metadata): void {
    // Check that the additional type property is set.
    if (empty($data['additionalType'])) {
      return;
    }

    $config = $this->configFactory
      ->get('schemadotorg_additional_type.settings');

    $types = [];
    $additional_types = (array) $data['additionalType'];
    $additional_types = array_combine($additional_types, $additional_types);
    foreach ($additional_types as $additional_type) {
      // Check if the additional type is ignored and should be removed.
      if (in_array($data['additionalType'], $config->get('ignored_types'))) {
        unset($additional_types[$additional_type]);
        continue;
      }

      // Set custom JSON-LD values.
      $schema_type = $mapping->getSchemaType();
      $jsonld_values = $config->get("jsonld_values.$schema_type--$additional_type")
        ?? $config->get("jsonld_values.$additional_type");
      if ($jsonld_values) {
        if (!empty($jsonld_values['type'])) {
          $types[$jsonld_values['type']] = $jsonld_values['type'];
        }
        if (!empty($jsonld_values['additional_type'])) {
          $additional_types[$jsonld_values['additional_type']] = $jsonld_values['additional_type'];
          unset($additional_types[$additional_type]);
        }
        continue;
      }

      // If the additional type property is a valid subtype, move it to
      // the @type and unset the additionalType property.
      $additional_schema_type = str_replace(' ', '_', $additional_type);
      $additional_schema_type = $this->schemaNames->snakeCaseToUpperCamelCase($additional_schema_type);
      if ($this->schemaTypeManager->isSubTypeOf($additional_schema_type, $data['@type'])) {
        $types[$additional_schema_type] = $additional_schema_type;
        unset($additional_types[$additional_type]);
      }
    }

    // Replace the @type with more specific additional types.
    if ($types) {
      $types = array_values($types);
      $data['@type'] = (count($types) === 1) ? $types[0] : $types;
    }

    // Update or remove the additional type property.
    $additional_types = array_values($additional_types);
    if ($additional_types) {
      $data['additionalType'] = (count($additional_types) === 1)
        ? $additional_types[0]
        : $additional_types;
    }
    else {
      unset($data['additionalType']);
    }
  }

}
