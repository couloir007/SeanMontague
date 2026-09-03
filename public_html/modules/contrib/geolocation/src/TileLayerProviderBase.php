<?php

namespace Drupal\geolocation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Defines an interface for geolocation TileLayer plugins.
 */
abstract class TileLayerProviderBase extends PluginBase implements TileLayerProviderInterface, ContainerFactoryPluginInterface {

  use TranslatorTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LayerFeatureManager $layerFeatureManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): TileLayerProviderInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.layerfeature')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    /* @noinspection PhpUnnecessaryLocalVariableInspection */
    $summary = [$this->getPluginDefinition()['name']];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], ?array $context = NULL): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerSettingsForm(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    return [];
  }

  /**
   * Return option part of ID string.
   *
   * @param string $tile_layer_option_id
   *   Tile Layer Option ID.
   *
   * @return string|null
   *   Option.
   */
  protected function getOptionTitleById(string $tile_layer_option_id): ?string {
    $parts = explode(':', $tile_layer_option_id);
    if (count($parts) == 1) {
      return $parts[0];
    }

    return $parts[1];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'default' => [
        'name' => $this->getPluginDefinition()['name'],
        'description' => $this->getPluginDefinition()['description'],
        'toggleable' => TRUE,
        'default_weight' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTileLayerUrl(string $tile_layer_option_id = 'default', array $settings = [], ?array $context = NULL): string {
    return "";
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    $options = $this->getLayerOptions();
    if ($options[$this->getOptionTitleById($tile_layer_option_id)]['name'] ?? FALSE) {
      return $this->getPluginDefinition()['name'] . ' - ' . $options[$this->getOptionTitleById($tile_layer_option_id)]['name'];
    }

    return $this->getPluginDefinition()['name'] ?? $this->t('Layer');
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(string $tile_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return $this->getPluginDefinition()['label'] ?? $this->t('Layer');
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, string $tile_layer_option_id = 'default', array $settings = [], array $context = []): array {
    $layer_settings = array_merge(self::getDefaultSettings($tile_layer_option_id, $settings, $context), [
      'settings' => $settings['settings'] ?? [],
      'label' => $this->getLabel($tile_layer_option_id, $settings, $context),
      'url' => $this->getTileLayerUrl($tile_layer_option_id, $settings, $context),
      'attribution' => $this->getAttribution($tile_layer_option_id, $settings, $context),
    ]);

    if (isset($settings['min_zoom'])) {
      $layer_settings['min_zoom'] = (int) $settings['min_zoom'];
    }

    if (isset($settings['max_zoom'])) {
      $layer_settings['max_zoom'] = (int) $settings['max_zoom'];
    }

    if (isset($settings['bounds'])) {
      $layer_settings['bounds'] = (array) $settings['bounds'];
    }

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments($render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                'tile_layers' => [
                  $tile_layer_option_id => $layer_settings,
                ],
              ],
            ],
          ],
        ],
      ]
    );

    return $render_array;
  }

}
