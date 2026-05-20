<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_recipe\FunctionalJavascript;

/**
 * Tests the JavaScript functionality of a Schema.org recipe.
 *
 * @group schemadotorg
 */
class SchemaDotRecipeJavaScriptTest extends SchemaDotRecipeWebDriverTestBase {

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
   * Test applying a recipe.
   */
  public function testApply(): void {
    // Check the event node type was created with the expected
    // custom description.
    // @see schemadotorg_recipe/tests/recipes/event/config/sync/schemadotorg_descriptions.settings.yml
    /** @var \Drupal\node\NodeTypeInterface $event_type */
    $event_type = $this->entityTypeManager
      ->getStorage('node_type')
      ->load('event');
    $this->assertEquals(
      'This is a custom description for Event.',
      $event_type->getDescription(),
    );
  }

}
