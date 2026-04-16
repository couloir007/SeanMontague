<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_recipe\Traits;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;

/**
 * Defines an abstract test base for Schema.org recipe tests.
 */
trait SchemaDotOrgRecipeTestTrait {

  /**
   * The path to the configuration directory.
   */
  protected static string $configDirectory;

  /**
   * The configuration names to import.
   */
  protected static array $configNames = [];

  /**
   * The path to the recipes directory.
   */
  protected static string $recipeDirectory;

  /**
   * The recipe names to apply.
   */
  protected static array $recipeNames = [];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The config storage service.
   */
  protected StorageInterface $configStorage;

  /**
   * Imports configuration files into the configuration storage.
   *
   * @param string|null $directory
   *   The directory from which configuration files should be imported. If not
   *   provided, a default directory specified by the class will be used.
   * @param array $names
   *   An array of specific configuration names to import. If empty, configuration
   *   names will be determined from the class hierarchy or the directory.
   */
  protected function importConfig(?string $directory = NULL, array $names = []): void {
    $directory = $directory ?? static::$configDirectory;

    // If no names are provided, collect $configNames from the class hierarchy.
    if (empty($names)) {
      $class = get_class($this);
      while ($class) {
        if (property_exists($class, 'configNames')) {
          $names = array_merge($names, $class::$configNames);
        }
        $class = get_parent_class($class);
      }
      $names = array_unique($names);
    }

    // If no names are provided, collect $names from the directory.
    if (empty($names)) {
      $names = array_keys($this->fileSystem->scanDirectory($directory, '/\.yml$/', ['key' => 'name']));
    }

    $source = new FileStorage($directory);
    foreach ($names as $name) {
      $data = $source->read($name);
      if ($data !== FALSE) {
        $this->configStorage->write($name, $data);
      }
      else {
        throw new \RuntimeException("Configuration file '$name' not found in directory '$directory'.");
      }
    }
  }

  /**
   * Applies recipes from the specified names or from the class hierarchy.
   *
   * @param array $names
   *   An array of recipes. If empty, the method will
   *   collect recipe names from the current class and its parent classes.
   */
  protected function applyRecipes(array $names = []): void {
    // If no recipe names are provided, collect recipe names
    // from the class hierarchy.
    if (empty($names)) {
      $class = get_class($this);
      while ($class) {
        if (property_exists($class, 'recipeNames')) {
          $names = array_merge($class::$recipeNames, $names);
        }
        $class = get_parent_class($class);
      }
      $names = array_unique($names);
    }

    $directory = static::$recipeDirectory;
    foreach ($names as $name) {
      $recipe = Recipe::createFromDirectory("$directory/$name");
      RecipeRunner::processRecipe($recipe);
      drupal_flush_all_caches();
    }
  }

}
