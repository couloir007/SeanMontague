<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_identifier;

use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org identifier manager interface.
 */
interface SchemaDotOrgIdentifierManagerInterface {

  /**
   * Add identifier field definitions to a content entity when a mapping is inserted.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void;

  /**
   * Get identifier field definitions for a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   *
   * @return array
   *   The identifier field definitions for a Schema.org mapping.
   */
  public function getMappingFieldDefinitions(SchemaDotOrgMappingInterface $mapping): array;

}
