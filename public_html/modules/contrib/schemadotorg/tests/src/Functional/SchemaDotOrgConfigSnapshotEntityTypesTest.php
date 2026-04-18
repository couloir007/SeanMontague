<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Functional;

/**
 * Tests the generated configuration files against a config snapshot.
 *
 * @group schemadotorg
 */
class SchemaDotOrgConfigSnapshotEntityTypesTest extends SchemaDotOrgConfigSnapshotTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg'];

  /**
   * {@inheritdoc}
   */
  protected string $snapshotDirectory = __DIR__ . '/../../schemadotorg/entity_types/config/snapshot';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create entity types.
    $entity_types = [
      'node:Place',
      'node:Organization',
      'node:Person',
      'node:Event',
      'node:Article',
      'node:WebPage',
      'node:Recipe',
    ];
    foreach ($entity_types as $entity_type) {
      [$entity_type_id, $schema_type] = explode(':', $entity_type);
      $this->createSchemaEntity($entity_type_id, $schema_type);
    }
  }

}
