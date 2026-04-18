<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_recipe\Functional;

/**
 * Tests the functionality of a Schema.org recipe.
 *
 * @group schemadotorg
 */
class SchemaDotOrgRecipeConfigSnapshotTest extends SchemaDotOrgRecipeConfigSnapshotTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_descriptions'];

  /**
   * {@inheritdoc}
   */
  protected static string $configDirectory = __DIR__ . '/../../recipes/schemadotorg_recipe_test/config/sync';

  /**
   * {@inheritdoc}
   */
  protected static string $recipeDirectory = __DIR__ . '/../../recipes';

  /**
   * {@inheritdoc}
   */
  protected static array $recipeNames = ['schemadotorg_recipe_test'];

  /**
   * {@inheritdoc}
   */
  protected string $snapshotDirectory = __DIR__ . '/../../config/snapshot';

}
