<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_recipe\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\schemadotorg_recipe\Traits\SchemaDotOrgRecipeTestTrait;

/**
 * Defines an abstract test base for Schema.org recipe functional tests.
 */
abstract class SchemaDotOrgRecipeBrowserTestBase extends BrowserTestBase {
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
