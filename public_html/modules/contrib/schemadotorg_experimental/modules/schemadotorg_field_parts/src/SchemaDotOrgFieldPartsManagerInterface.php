<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_field_parts;

use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org identifier manager interface.
 */
interface SchemaDotOrgFieldPartsManagerInterface {

  /**
   * The field prefix.
   */
  const PREFIX = 'prefix';

  /**
   * The field suffix.
   */
  const SUFFIX = 'suffix';

  /**
   * The field parts.
   */
  const PARTS = ['prefix', 'suffix'];

  /**
   * Add field parts to a Schema.org mappings' field before a mapping is saved.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingPresave(SchemaDotOrgMappingInterface $mapping): void;

}
