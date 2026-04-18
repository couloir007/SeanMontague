<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_content_moderation;

use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org content moderation manager interface.
 */
interface SchemaDotOrgContentModerationManagerInterface {

  /**
   * Enable content moderation when a mapping is inserted.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void;

}
