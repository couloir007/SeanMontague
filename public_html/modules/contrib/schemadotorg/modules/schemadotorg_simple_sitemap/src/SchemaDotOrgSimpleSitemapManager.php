<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_simple_sitemap;

use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\simple_sitemap\Manager\Generator;

/**
 * The Schema.org simple sitemap manager.
 */
class SchemaDotOrgSimpleSitemapManager implements SchemaDotOrgSimpleSitemapManagerInterface {

  /**
   * Constructs a SchemaDotOrgSimpleSitemapManager object.
   *
   * @param \Drupal\simple_sitemap\Manager\Generator $generator
   *   The simple sitemap generator service.
   */
  public function __construct(
    protected Generator $generator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    // Only add nodes to the sitemap.xml.
    if ($entity_type_id !== 'node') {
      return;
    }

    // Set an entity type to be indexed.
    $this->generator
      ->entityManager()
      ->enableEntityType('node')
      ->setSitemaps(['default'])
      ->setBundleSettings('node', $bundle, ['index' => TRUE]);

    $this->generator->rebuildQueue();
  }

}
