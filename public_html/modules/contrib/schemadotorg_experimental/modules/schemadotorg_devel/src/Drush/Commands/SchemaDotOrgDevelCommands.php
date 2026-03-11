<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_devel\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface;
use Drupal\schemadotorg\Utility\SchemaDotOrgHtmlHelper;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Devel Drush commands.
 */
final class SchemaDotOrgDevelCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * Constructs a SchemaDotOrgDevelCommands object.
   */
  public function __construct(
    protected ModuleExtensionList $moduleExtensionList,
    protected ExtensionPathResolver $extensionPathResolver,
    protected ConfigFactoryInterface $configFactory,
    protected FileSystemInterface $fileSystem,
    protected SchemaDotOrgConfigManagerInterface $schemaConfigManager,
  ) {}

  /**
   * Repair the configuration for Schema.org Blueprints module.
   *
   * @command schemadotorg:repair-config
   *
   * @usage schemadotorg:repair-config
   */
  public function repairConfig(): void {
    // @todo Import and export config using features.
    $this->schemaConfigManager->repair();
  }

  /**
   * Repair the configuration for Schema.org Blueprints module.
   *
   * @command schemadotorg:convert-field_groups-to-field-weights
   *
   * @usage schemadotorg:convert-field_groups-to-field-weights
   */
  public function convertFieldGroupsToFieldWeights(string $entity_type_id = 'node'): void {
    $default_field_groups = $this->configFactory
      ->get('schemadotorg_field_group.settings')
      ->get('default_field_groups.' . $entity_type_id);
    if (empty($default_field_groups)) {
      return;
    }

    $default_field_weights = [];
    foreach ($default_field_groups as $default_field_group) {
      $default_field_weights = array_merge($default_field_weights, $default_field_group['properties']);
    }

    $this->configFactory
      ->getEditable('schemadotorg.settings')
      ->set('schema_properties.default_field_weights', $default_field_weights)
      ->save();
  }

  /**
   * Generate help HTML for Schema.org Blueprints sub-modules.
   *
   * @command schemadotorg:generate-help
   *
   * @usage schemadotorg:generate-help
   */
  public function generateHelp(): void {
    $schemadotorg_path = $this->extensionPathResolver->getPath('module', 'schemadotorg');
    $help_path = $schemadotorg_path . '/help';

    // Delete the /html directory.
    if (file_exists($help_path)) {
      $this->fileSystem->deleteRecursive($help_path);
    }

    // Convert schemadotorg* modules to README.md to HTMl.
    $this->fileSystem->mkdir(
      uri: $help_path . '/modules',
      recursive: TRUE,
    );
    $module_names = array_keys($this->moduleExtensionList->getAllAvailableInfo());
    $module_names = array_filter(
      $module_names,
      fn($module_name) => str_starts_with($module_name, 'schemadotorg')
    );
    foreach ($module_names as $module_name) {
      $readme_path = $this->extensionPathResolver->getPath('module', $module_name) . '/README.md';
      if (!file_exists($readme_path)) {
        continue;
      }

      $markdown = file_get_contents($readme_path);
      $html = SchemaDotOrgHtmlHelper::fromMarkdown($markdown);
      file_put_contents($help_path . '/modules/' . $module_name . '.html', $html);
    }

    // Convert docs/*.md to HTMl.
    $this->fileSystem->mkdir(
      uri: $help_path . '/docs',
      recursive: TRUE,
    );
    $files = $this->fileSystem->scanDirectory($schemadotorg_path . '/docs', '/.md$/');
    foreach ($files as $readme_path => $file) {
      $markdown = file_get_contents($readme_path);
      $html = SchemaDotOrgHtmlHelper::fromMarkdown($markdown);
      file_put_contents($help_path . '/docs/' . strtolower($file->name) . '.html', $html);
    }
  }

  /**
   * Generate MODULE.features.yml for Schema.org Blueprints sub-modules.
   *
   * @command schemadotorg:generate-features
   *
   * @usage schemadotorg:generate-features
   */
  public function generateFeatures(): void {
    if (!$this->io()
      ->confirm(dt('Are you sure you want to generate MODULE.features.yml for all Schema.org Blueprints sub-modules?'))) {
      throw new UserAbortException();
    }

    $module_names = array_keys($this->moduleExtensionList->getAllAvailableInfo());
    $module_names = array_filter(
      $module_names,
      fn($module_name) => str_starts_with($module_name, 'schemadotorg')
    );
    foreach ($module_names as $module_name) {
      $module_path = $this->extensionPathResolver->getPath('module', $module_name);
      $features_path = "$module_path/$module_name.features.yml";
      if (!file_exists($features_path)) {
        $this->output()->writeln("Creating $features_path.");
        file_put_contents($features_path, 'true' . PHP_EOL);
      }
      else {
        $this->output()->writeln("Skipping $features_path.");
      }
    }
  }

  /**
   * Validates the entity type and Schema.org type to be created.
   *
   * @hook validate schemadotorg:tidy-yaml
   */
  public function tidyYamlValidate(CommandData $commandData): void {
    $arguments = $commandData->getArgsWithoutAppName();
    $path = $arguments['path'] ?? '';
    if (empty($path)) {
      throw new \Exception(dt('Path is required.'));
    }

    if (!file_exists(DRUPAL_ROOT . '/' . $path) && !file_exists($path)) {
      throw new \Exception(dt("A valid path is required. $path"));
    }
  }

  /**
   * Tidies all YAML configuration files.
   *
   * @param string $path
   *   The path to tidy all YAML configuration files.
   *
   * @command schemadotorg:tidy-yaml
   *
   * @usage schemadotorg:tidy-yaml /some/path
   */
  public function tidyYaml(string $path): void {
    if (!file_exists($path)) {
      $path = DRUPAL_ROOT . '/' . $path;
    }

    $t_args = ['@path' => $path];
    if (!$this->io()
      ->confirm(dt('Are you sure you want to tidy all YAML files in @path?', $t_args))) {
      throw new UserAbortException();
    }

    $files = $this->fileSystem->scanDirectory($path, '/\.yml$/');
    foreach ($files as $file_path => $file) {
      $file_name = $file->filename;
      if (preg_match('/\.(schemadotorg_starterkit|services|features|libraries)\.yml$/', $file_name)) {
        continue;
      }

      $contents = file_get_contents($file_path);
      if (str_contains($contents, '# ')) {
        $this->output()->writeln("Skipping $file_name.");
      }
      else {
        $this->output()->writeln("Tidying $file_name.");
        $data = Yaml::decode($contents);
        $yaml = Yaml::encode($data);
        // Remove return after array delimiter.
        $yaml = preg_replace('#((?:\n|^)[ ]*-)\n[ ]+(\w|[\'"])#', '\1 \2', $yaml);
        file_put_contents($file_path, $yaml);
      }
    }
  }

  /**
   * Track execution time.
   */
  protected float $startTime;

  /**
   * Register new 'memory-limit' options for 'pm:install' command.
   *
   * @hook command pm:install
   * @option execution-time Display execution time post command execution.
   * @option memory-limit Display memory limit post command execution.
   */
  public function pmInstall(CommandData $commandData): void {
    $options = $commandData->options();
    if (!empty($options['execution-time'])) {
      $this->startTime = microtime(TRUE);
    }
  }

  /**
   * Register new 'memory-limit' options for 'pm:install' command.
   *
   * @hook command schemadotorg:starterkit-install
   * @option execution-time Display execution time post command execution.
   * @option memory-limit Display memory limit post command execution.
   */
  public function schemadotorgStarterkitInstall(CommandData $commandData): void {
    $options = $commandData->options();
    if (!empty($options['execution-time'])) {
      $this->startTime = microtime(TRUE);
    }
  }

  /**
   * Post install command.
   *
   * @hook post-command *
   */
  public function postCommand(mixed $result, CommandData $commandData): void {
    $options = $commandData->options();

    // Memory limit;.
    if (!empty($options['memory-limit'])) {
      // @phpstan-ignore-next-line
      $this->io()->note(
        \sprintf(
          'PHP memory usage: %.1f MB',
          memory_get_usage() / 1024 / 1024
        )
      );
    }

    // Execution time.
    if (!empty($options['execution-time'])) {
      $end_time = microtime(TRUE);
      $execution_time = $end_time - $this->startTime;

      // @phpstan-ignore-next-line
      $this->io()->note(
        \sprintf(
          "PHP execution time: %02d:%02d minutes",
          floor($execution_time / 60),
          $execution_time % 60,
        )
      );
    }
  }

}
