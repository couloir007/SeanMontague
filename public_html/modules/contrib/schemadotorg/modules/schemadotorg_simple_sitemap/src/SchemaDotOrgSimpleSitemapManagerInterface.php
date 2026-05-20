<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_simple_sitemap;

use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org simple sitemap manager interface.
 */
interface SchemaDotOrgSimpleSitemapManagerInterface {

  /**
   * Enable simple sitemap for a node when a mapping is inserted.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void;

}
