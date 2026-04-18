<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_starterkit_organization\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgConfigSnapshotTestBase;

/**
 * Tests the generated configuration files against a config snapshot.
 *
 * @group schemadotorg
 */
class SchemaDotOrgStarterKitOrganizationConfigSnapshotTest extends SchemaDotOrgConfigSnapshotTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_starterkit_organization'];

  /**
   * {@inheritdoc}
   */
  protected string $snapshotDirectory = __DIR__ . '/../../schemadotorg/config/snapshot';

}
