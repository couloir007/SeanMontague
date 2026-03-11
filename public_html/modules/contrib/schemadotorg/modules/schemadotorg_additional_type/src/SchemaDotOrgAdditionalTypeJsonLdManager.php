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
    // Check that the additional type property is set and is string, if not exit.
    if (empty($data['additionalType']) || !is_string($data['additionalType'])) {
      return;
    }

    // Check if the additional type is ignored and should be removed.
    $additional_type = $data['additionalType'];
    $ignored_types = $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('ignored_types');
    if (in_array($additional_type, $ignored_types)) {
      unset($data['additionalType']);
      return;
    }

    // If the additional type property is valid subtype move it to the @type
    // and unset the additionalType property.
    $additional_schema_type = str_replace(' ', '_', $data['additionalType']);
    $additional_schema_type = $this->schemaNames->snakeCaseToUpperCamelCase($additional_schema_type);
    if ($this->schemaTypeManager->isSubTypeOf($additional_schema_type, $data['@type'])) {
      $data['@type'] = $additional_schema_type;
      unset($data['additionalType']);
    }
  }

}
