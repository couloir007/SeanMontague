<?php

namespace Drupal\geolocation_maplibre;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\geolocation\MapProviderInterface;

/**
 * Defines an interface for base map plugins.
 *
 * A base map is the map itself: one style document naming the sources, layers,
 * sprites and fonts a vector renderer draws. Unlike a tile layer, of which any
 * number stack on a map, a map has exactly one - so a base map is chosen
 * rather than enabled, and brings whatever credentials its service wants.
 */
interface BaseMapInterface extends PluginInspectionInterface {

  /**
   * Get default settings.
   *
   * @return array
   *   Default settings.
   */
  public static function getDefaultSettings(): array;

  /**
   * Get settings, with defaults applied.
   *
   * @param array $settings
   *   Settings.
   *
   * @return array
   *   Settings.
   */
  public function getSettings(array $settings): array;

  /**
   * Provide a summary array.
   *
   * @param array $settings
   *   Settings.
   *
   * @return array
   *   An array to use as field formatter summary.
   */
  public function getSettingsSummary(array $settings): array;

  /**
   * Get settings form.
   *
   * @param array $settings
   *   Settings.
   * @param array $parents
   *   Form parents.
   *
   * @return array
   *   Settings form.
   */
  public function getSettingsForm(array $settings, array $parents = []): array;

  /**
   * Get the URL of the style document to render.
   *
   * @param array $settings
   *   Settings.
   *
   * @return string
   *   Style URL.
   */
  public function getStyleUrl(array $settings): string;

  /**
   * Get the attribution the service requires.
   *
   * Styles carry their own attribution as a rule, so this is only for a
   * service that expects more than its style document declares.
   *
   * @param array $settings
   *   Settings.
   *
   * @return string
   *   Attribution, empty where the style covers it.
   */
  public function getAttribution(array $settings): string;

  /**
   * Alter the map render array.
   *
   * Whatever the base map contributes to the map - the style to draw, the
   * attribution it wants shown, anything its service needs of the renderer -
   * it attaches here, rather than the map provider knowing which plugins
   * need what.
   *
   * @param array $render_array
   *   Render array.
   * @param array $settings
   *   Settings.
   * @param \Drupal\geolocation\MapProviderInterface $map_provider
   *   Map provider rendering this base map.
   *
   * @return array
   *   Render array.
   */
  public function alterMap(array $render_array, array $settings, MapProviderInterface $map_provider): array;

}
