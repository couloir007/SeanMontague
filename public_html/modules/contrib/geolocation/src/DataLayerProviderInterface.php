<?php

namespace Drupal\geolocation;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for geolocation DataLayer plugins.
 */
interface DataLayerProviderInterface extends PluginInspectionInterface {

  /**
   * Provide a summary array.
   *
   * @return array
   *   An array to use as field formatter summary.
   */
  public function getSettingsSummary(string $data_layer_option_id, array $settings = [], ?array $context = NULL): array;

  /**
   * Get settings form.
   *
   * @param string $data_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return array
   *   Settings form.
   */
  public function getSettingsForm(string $data_layer_option_id, array $settings = [], ?array $context = NULL): array;

  /**
   * Get layer label.
   *
   * @param string $data_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return string
   *   Label.
   */
  public function getLabel(string $data_layer_option_id, array $settings = [], ?array $context = NULL): string;

  /**
   * Get available data layer options.
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
   * @param string $data_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array|null $context
   *   Context.
   *
   * @return array
   *   Render array for layer.
   */
  public function getLayerRenderData(string $data_layer_option_id, array $settings = [], ?array $context = NULL): array;

  /**
   * Alter map render array.
   *
   * @param array $render_array
   *   Render array.
   * @param string $data_layer_option_id
   *   Data Layer Option ID.
   * @param array $settings
   *   Settings.
   * @param array $context
   *   Context.
   *
   * @return array
   *   Render array for map.
   */
  public function alterMap(array $render_array, string $data_layer_option_id = 'default', array $settings = [], array $context = []): array;

}
