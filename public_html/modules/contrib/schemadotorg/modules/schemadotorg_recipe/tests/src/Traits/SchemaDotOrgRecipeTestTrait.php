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
use Drupal\Tests\schemadotorg\Traits\SchemaDotOrgTestTrait;

/**
 * Defines an abstract test base for Schema.org recipe tests.
 */
trait SchemaDotOrgRecipeTestTrait {
  use SchemaDotOrgTestTrait;

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

  /**
   * Normalizes configuration so Drupal 10 and 11 snapshots are similar.
   */
  protected function normalizeConfig(): void {
    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($node_types as $node_type) {
      $form_display = $this->entityTypeManager
        ->getStorage('entity_form_display')
        ->load('node.' . $node_type->id() . '.default');
      if ($form_display) {
        $form_display->removeComponent('promote');
        $form_display->removeComponent('sticky');
        $form_display->save();
      }
    }

    if (version_compare(\Drupal::VERSION, '11.0.0', '<')) {
      // @todo Remove Drupal 10 compatibility normalization once only Drupal 11 is supported.
      $this->normalizeDrupal10NodeViewDisplays();
      $this->removeDrupal10NodeSearchViewModes();
      \Drupal::configFactory()->reset();
    }
  }

  /**
   * Normalizes Drupal 10 node view displays to match Drupal 11 snapshots.
   */
  protected function normalizeDrupal10NodeViewDisplays(): void {
    $config_names = $this->configStorage->listAll('core.entity_view_display.node.');
    foreach ($config_names as $config_name) {
      $config_data = $this->configStorage->read($config_name);
      if (!is_array($config_data)) {
        continue;
      }

      $config_changed = FALSE;
      foreach (($config_data['content'] ?? []) as $component_name => $component) {
        if (array_key_exists('link_to_entity', ($component['settings'] ?? [])) && !isset($component['settings']['link_rel'])) {
          $config_data['content'][$component_name]['settings']['link_rel'] = 'canonical';
          $config_changed = TRUE;
        }
      }

      if ($config_changed) {
        $this->configStorage->write($config_name, $config_data);
      }
    }
  }

  /**
   * Removes Drupal 10 node search view modes that Drupal 11 no longer creates.
   */
  protected function removeDrupal10NodeSearchViewModes(): void {
    foreach ([
      'core.entity_view_mode.node.search_index',
      'core.entity_view_mode.node.search_result',
    ] as $config_name) {
      if ($this->configStorage->read($config_name) !== FALSE) {
        $this->configStorage->delete($config_name);
      }
    }
  }

}
