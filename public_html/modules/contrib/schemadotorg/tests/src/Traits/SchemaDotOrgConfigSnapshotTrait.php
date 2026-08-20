<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Traits;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\File\FileSystemInterface;

/**
 * Provides convenience methods for Schema.org config snap test and assertion.
 */
trait SchemaDotOrgConfigSnapshotTrait {

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Sets up the configuration snapshot directory.
   */
  protected function setUpConfigSnapshot(): void {
    $this->fileSystem = \Drupal::service('file_system');

    // Make sure that the snapshot directory is defined.
    if (!isset($this->snapshotDirectory)) {
      throw new \Exception('Snapshot directory is required.');
    }

    // If the snapshot does not exist, create it.
    if (!file_exists($this->snapshotDirectory)) {
      $this->fileSystem->mkdir($this->snapshotDirectory, 0777, TRUE);
    }
  }

  /**
   * Test Schema.org Blueprints config snapshot.
   */
  public function testConfigSnapshot(): void {
    // Get current config snapshot filenames.
    $expected_files = $this->fileSystem->scanDirectory($this->snapshotDirectory, '/\.yml$/', ['key' => 'filename']);
    $expected_filenames = array_keys($expected_files);
    $expected_filenames = array_combine($expected_filenames, $expected_filenames);
    ksort($expected_filenames);
    $this->normalizeConfigSnapshotFilenames($expected_filenames);

    // Get config prefixes to test.
    $config_prefixes = [];
    $class = get_class($this);
    while ($class) {
      if (property_exists($class, 'configPrefixes')) {
        $config_prefixes = array_merge($config_prefixes, $class::$configPrefixes);
      }
      $class = get_parent_class($class);
    }
    $config_prefixes = array_unique($config_prefixes);

    // Track the actual config snapshot filenames.
    $actual_filenames = [];
    foreach ($config_prefixes as $config_prefix) {
      $config_names = \Drupal::configFactory()->listAll($config_prefix);
      foreach ($config_names as $config_name) {
        $config_file_name = $config_name . '.yml';
        $config_file_path = $this->snapshotDirectory . '/' . $config_file_name;

        // Get raw config data.
        $config_data = \Drupal::config($config_name)->getRawData();

        // Remove any property that could contain a UUID,
        // which will make each snapshot unique.
        unset(
          $config_data['uuid'],
          $config_data['icon_uuid'],
          $config_data['_core'],
          $config_data['dependencies']['content'],
          $config_data['selection_criteria'],
        );
        $this->normalizeConfigSnapshotData($config_name, $config_data);

        // Create config snapshot if it does not exist.
        // @todo Determine if we need to notify the user via CLI.
        if (!file_exists($config_file_path)) {
          file_put_contents($config_file_path, Yaml::encode($config_data));
        }

        // Always test the config snapshot.
        $config_snapshot_data = Yaml::decode(file_get_contents($config_file_path));
        $this->normalizeConfigSnapshotData($config_name, $config_snapshot_data);
        $this->assertEquals($config_data, $config_snapshot_data, sprintf('Config snapshot matches for %s', $config_file_name));

        $actual_filenames[$config_file_name] = $config_file_name;
      }
    }

    // Check that no config snapshot files were generated.
    $this->assertEquals($expected_filenames, $actual_filenames, 'No new config snapshot files were generated. If config snapshot files were generated as expected, please re-run this test.');
  }

  /**
   * Normalizes configuration snapshot filenames that differ by Drupal version.
   *
   * @param array $filenames
   *   The configuration snapshot filenames.
   */
  protected function normalizeConfigSnapshotFilenames(array &$filenames): void {
    if (version_compare(\Drupal::VERSION, '11.0.0', '<')) {
      return;
    }

    // @todo Remove Drupal 10 compatibility normalization once only Drupal 11 is supported.
    unset(
      $filenames['core.entity_view_mode.node.search_index.yml'],
      $filenames['core.entity_view_mode.node.search_result.yml'],
    );
  }

  /**
   * Normalizes configuration data that differs between Drupal 10 and 11.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data.
   */
  protected function normalizeConfigSnapshotData(string $config_name, array &$config_data): void {
    if (str_starts_with($config_name, 'core.entity_form_display.node.')) {
      $this->normalizeNodeFormDisplayConfigSnapshotData($config_data);
      return;
    }

    if (str_starts_with($config_name, 'core.entity_view_display.node.')) {
      $this->normalizeNodeViewDisplayConfigSnapshotData($config_name, $config_data);
      return;
    }

    if (str_starts_with($config_name, 'field.field.node.')
      && str_ends_with($config_name, '.body')) {
      $this->normalizeNodeBodyFieldConfigSnapshotData($config_data);
    }
  }

  /**
   * Normalizes node form display configuration data.
   *
   * @param array $config_data
   *   The configuration data.
   */
  protected function normalizeNodeFormDisplayConfigSnapshotData(array &$config_data): void {
    foreach (['promote', 'sticky'] as $component_name) {
      if (isset($config_data['content'][$component_name])) {
        unset($config_data['content'][$component_name]);
        $config_data['hidden'][$component_name] = TRUE;
      }
    }

    if (($config_data['content']['body']['type'] ?? NULL) === 'text_textarea_with_summary') {
      $config_data['content']['body']['type'] = 'text_textarea';
      $config_data['content']['body']['settings']['rows'] = 5;
      unset(
        $config_data['content']['body']['settings']['summary_rows'],
        $config_data['content']['body']['settings']['show_summary'],
      );
    }
  }

  /**
   * Normalizes node view display configuration data.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data.
   */
  protected function normalizeNodeViewDisplayConfigSnapshotData(string $config_name, array &$config_data): void {
    foreach (($config_data['content'] ?? []) as $component_name => $component) {
      if (($component['settings']['link_to_entity'] ?? NULL) === FALSE) {
        unset($config_data['content'][$component_name]['settings']['link_rel']);
      }
    }

    if (!str_ends_with($config_name, '.teaser')) {
      return;
    }

    if (($config_data['content']['body']['type'] ?? NULL) === 'text_summary_or_trimmed') {
      $config_data['content']['body']['type'] = 'text_default';
      $config_data['content']['body']['label'] = 'above';
      $config_data['content']['body']['settings'] = [];
    }
  }

  /**
   * Normalizes node body field instance configuration data.
   *
   * @param array $config_data
   *   The configuration data.
   */
  protected function normalizeNodeBodyFieldConfigSnapshotData(array &$config_data): void {
    if (($config_data['field_type'] ?? NULL) !== 'text_long') {
      return;
    }

    $config_data['field_type'] = 'text_with_summary';
    $config_data['settings']['display_summary'] = FALSE;
    $config_data['settings']['required_summary'] = FALSE;
  }

}
