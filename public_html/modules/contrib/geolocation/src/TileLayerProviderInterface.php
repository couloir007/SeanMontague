<?php

namespace Drupal\geolocation;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for geolocation TileLayer plugins.
 */
interface TileLayerProviderInterface extends PluginInspectionInterface {

  /**
   * Get default settings.
   *
   * @param string $tile_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return array
   *   Default Settings.
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array;

  /**
   * Provide a summary array.
   *
   * @return array
   *   An array to use as field formatter summary.
   */
  public function getSettingsSummary(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array;

  /**
   * Get settings form.
   *
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return array
   *   Settings form.
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array;

  /**
   * Get layer settings form.
   *
   * @param string $tile_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return array
   *   Settings form.
   */
  public function getLayerSettingsForm(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array;

  /**
   * Get layer label.
   *
   * @param string $tile_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return string
   *   Label.
   */
  public function getLabel(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string;

  /**
   * Get layer attribution.
   *
   * @param string $tile_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return string
   *   Attribution.
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string;

  /**
   * Get available tile layer options.
   *
   * ```
   * return [
   *   'default' => [
   *     'name' => $this->getPluginDefinition()['name'],
   *     'description' => $this->getPluginDefinition()['description'],
   *     'switchable' => TRUE,
   *     'default_weight' => 0,
   *   ],
   * ];
   * ```
   *
   * @param array|null $context
   *   Context.
   *
   * @return array
   *   Available layer Options.
   */
  public function getLayerOptions(?array $context = NULL): array;

  /**
   * Get layer render array.
   *
   * @param string $tile_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return string
   *   Tile layer URL.
   */
  public function getTileLayerUrl(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string;

  /**
   * Alter map render array.
   *
   * @param array $render_array
   *   Render array.
   * @param string $tile_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array $context
   *   Context.
   *
   * @return array
   *   Render array for map.
   */
  public function alterMap(array $render_array, string $tile_layer_option_id = 'default', array $settings = [], array $context = []): array;

}
