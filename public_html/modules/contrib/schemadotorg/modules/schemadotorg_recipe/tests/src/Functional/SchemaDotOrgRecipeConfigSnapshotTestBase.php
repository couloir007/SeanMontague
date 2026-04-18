<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_recipe\Functional;

use Drupal\Tests\schemadotorg\Traits\SchemaDotOrgConfigSnapshotTrait;

/**
 * Base tests for Schema.org Blueprints config snapshot.
 *
 * For working instance of this base test see SchemaDotOrgConfigSnapshotTest.
 *
 * To create a config snapshot (../../schemadotorg/config/snapshot).
 *
 * - Create a config snapshot test by copying and adjusting
 *   SchemaDotOrgConfigSnapshotTest.php.
 * - Run the test to creates the initial snapshot.
 *   This test will fail because snapshot files are being generated
 * - Re-run the test and confirm that config snapshot passes as expected.
 * - Commit the test and the config snapshot.
 *
 * To update a config snapshot (../../schemadotorg/config/snapshot).
 *
 * - Delete the config snapshot (../../schemadotorg/config/snapshot)
 * - Re-run the test to re-create the snapshot.
 *   This test will fail because snapshot files are being generated
 * - Re-run the test and confirm that config snapshot passes as expected.
 * - Re-commit the config snapshot.
 *
 * @see \Drupal\Tests\schemadotorg\Functional\SchemaDotOrgConfigSnapshotEntityTypesTest
 */
abstract class SchemaDotOrgRecipeConfigSnapshotTestBase extends SchemaDotOrgRecipeBrowserTestBase {
  use SchemaDotOrgConfigSnapshotTrait;

  // phpcs:disable
  /**
   * Disable config schema checking.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * The Schema.org Blueprints config snapshot directory.
   */
  protected string $snapshotDirectory;

  /**
   * Configuration file prefixes to create and test snapshots.
   *
   * The below list of file prefix targets any configuration generated
   * by the core Schema.org Blueprints module.
   */
  protected static array $configPrefixes = [
    'block_content.type.',
    'core.entity_form_display.',
    'core.entity_form_mode.',
    'core.entity_view_display.',
    'core.entity_view_mode.',
    'core.base_field_override.',
    'field.field.',
    'field.storage.',
    'node.type',
    'media.type',
    'paragraphs.paragraphs_type.',
    'schemadotorg.',
    'taxonomy.vocabulary.',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpConfigSnapshot();
  }

}
