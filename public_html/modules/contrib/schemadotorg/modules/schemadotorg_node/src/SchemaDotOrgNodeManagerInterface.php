<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_node;

use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org bode manager interface.
 */
interface SchemaDotOrgNodeManagerInterface {

  /**
   * Alter node settings for a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void;

}
