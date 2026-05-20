<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_recipe\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\schemadotorg_recipe\SchemaDotOrgRecipeManagerInterface;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Schema.org recipe Drush commands.
 */
class SchemaDotOrgRecipeCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * Constructs a SchemaDotOrgRecipeCommands object.
   *
   * @param \Drupal\schemadotorg_recipe\SchemaDotOrgRecipeManagerInterface $recipeManager
   *   The Schema.org recipe manager.
   */
  public function __construct(
    protected SchemaDotOrgRecipeManagerInterface $recipeManager,
  ) {}

  /* ************************************************************************ */
  // Info.
  /* ************************************************************************ */

  /**
   * Allow users to choose the recipe to be outputted.
   *
   * @hook interact schemadotorg:recipe-info
   */
  public function infoInteract(InputInterface $input): void {
    $this->interactChooseRecipe($input, dt('info'));
  }

  /**
   * Validates the Schema.org recipe info.
   *
   * @hook validate schemadotorg:recipe-info
   */
  public function infoValidate(CommandData $commandData): void {
    $this->validateRecipe($commandData);
  }

  /**
   * Outputs a Schema.org recipes information in Markdown.
   *
   * @param string $name
   *   The name of recipe.
   *
   * @command schemadotorg:recipe-info
   *
   * @usage drush schemadotorg:recipe-info schemadotorg_recipe_events
   */
  public function info(string $name): void {
    $settings = $this->recipeManager->getRecipeSettings($name);
    $this->output()->writeln('Types');
    $this->output()->writeln('');
    foreach ($settings['schemadotorg']['types'] as $type => $mapping_defaults) {
      [, $schema_type] = explode(':', $type);
      $uri = 'https://schema.org/' . $schema_type;

      $this->output()->writeln('- **' . $mapping_defaults['entity']['label'] . '** (' . $type . ')  ');
      if ($mapping_defaults['entity']['description']) {
        $this->output()->writeln('  ' . $mapping_defaults['entity']['description'] . '  ');
      }
      $this->output()->writeln('  <' . $uri . '>');
      $this->output()->writeln('');
    }
  }

  /* ************************************************************************ */
  // Apply.
  /* ************************************************************************ */

  /**
   * Allow users to choose the recipe to be applied.
   *
   * @hook interact schemadotorg:recipe-apply
   */
  public function applyInteract(InputInterface $input): void {
    $this->interactChooseRecipe($input, 'apply');
  }

  /**
   * Validates the Schema.org recipe apply.
   *
   * @hook validate schemadotorg:recipe-apply
   */
  public function applyValidate(CommandData $commandData): void {
    $this->validateRecipe($commandData);
  }

  /**
   * Setup the Schema.org recipe.
   *
   * @param string $name
   *   The name of recipe.
   *
   * @command schemadotorg:recipe-apply
   *
   * @usage drush schemadotorg:recipe-apply schemadotorg_recipe_events
   */
  public function apply(string $name): void {
    $this->confirmRecipe($name, 'apply', TRUE);
    $this->recipeManager->apply($name);
  }

  /* ************************************************************************ */
  // Generate.
  /* ************************************************************************ */

  /**
   * Allow users to choose the recipe to generate.
   *
   * @hook interact schemadotorg:recipe-generate
   */
  public function generateInteract(InputInterface $input): void {
    $this->interactChooseRecipe($input, 'generate');
  }

  /**
   * Validates the Schema.org recipe generate.
   *
   * @hook validate schemadotorg:recipe-generate
   */
  public function generateValidate(CommandData $commandData): void {
    $this->validateRecipe($commandData);
  }

  /**
   * Generate the Schema.org recipe.
   *
   * @param string $name
   *   The name of recipe.
   *
   * @command schemadotorg:recipe-generate
   *
   * @usage drush schemadotorg:recipe-generate schemadotorg_starterkit_events
   */
  public function generate(string $name): void {
    $this->confirmRecipe($name, 'generate');
    $this->recipeManager->generate($name);
  }

  /* ************************************************************************ */
  // Kill.
  /* ************************************************************************ */

  /**
   * Allow users to choose the recipe to kill.
   *
   * @hook interact schemadotorg:recipe-kill
   */
  public function killInteract(InputInterface $input): void {
    $this->interactChooseRecipe($input, 'kill');
  }

  /**
   * Validates the Schema.org recipe kill.
   *
   * @hook validate schemadotorg:recipe-kill
   */
  public function killValidate(CommandData $commandData): void {
    $this->validateRecipe($commandData);
  }

  /**
   * Kill the Schema.org recipe.
   *
   * @param string $name
   *   The name of recipe.
   *
   * @command schemadotorg:recipe-kill
   *
   * @usage drush schemadotorg:recipe-kill schemadotorg_recipe_events
   */
  public function kill(string $name): void {
    $this->confirmRecipe($name, 'kill');
    $this->recipeManager->kill($name);
  }

  /* ************************************************************************ */
  // Command helper methods.
  /* ************************************************************************ */

  /**
   * Allow users to choose the recipe.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The user input.
   * @param string $action
   *   The action.
   */
  protected function interactChooseRecipe(InputInterface $input, string $action): void {
    $name = $input->getArgument('name');
    if ($name) {
      return;
    }

    switch ($action) {
      case 'apply':
        $recipes = $this->recipeManager->getRecipes();
        break;

      default:
        $recipes = $this->recipeManager->getRecipes(TRUE);
        break;
    }

    $action_translated = dt($action);

    if (empty($recipes)) {
      throw new \Exception(dt('There are no Schema.org recipes to @action', ['@action' => $action_translated]));
    }

    $recipes = array_keys($recipes);
    $choices = array_combine($recipes, $recipes);
    $choice = $this->io()->choice(dt('Choose a Schema.org recipe to @action', ['@action' => $action_translated]), $choices);
    $input->setArgument('name', $choice);
  }

  /**
   * Validates the Schema.org recipe name.
   */
  protected function validateRecipe(CommandData $commandData): void {
    $arguments = $commandData->getArgsWithoutAppName();
    $name = $arguments['name'] ?? '';
    $recipe = $this->recipeManager->getRecipe($name);
    if (!$recipe) {
      throw new \Exception(dt("Schema.org recipe '@name' not found.", ['@name' => $name]));
    }
  }

  /**
   * Schema.org recipe confirm action.
   *
   * @param string $name
   *   The recipe name.
   * @param string $action
   *   The recipe action.
   * @param bool $required
   *   Include required types.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  protected function confirmRecipe(string $name, string $action, bool $required = FALSE): void {
    $t_args = [
      '@action' => $action,
      '@name' => $name,
    ];
    if (!$this->io()->confirm(dt("Are you sure you want to @action the '@name' recipe?", $t_args))) {
      throw new UserAbortException();
    }
  }

}
