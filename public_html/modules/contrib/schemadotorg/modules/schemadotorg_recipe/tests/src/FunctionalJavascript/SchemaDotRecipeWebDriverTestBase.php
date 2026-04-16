<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_recipe\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\schemadotorg_recipe\Traits\SchemaDotOrgRecipeTestTrait;

/**
 * Defines an abstract test base for Schema.org recipe JavaScript tests.
 */
abstract class SchemaDotRecipeWebDriverTestBase extends WebDriverTestBase {
  use SchemaDotOrgRecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->fileSystem = $this->container->get('file_system');
    $this->configStorage = $this->container->get('config.storage');

    $this->importConfig();
    $this->applyRecipes();
  }

}
