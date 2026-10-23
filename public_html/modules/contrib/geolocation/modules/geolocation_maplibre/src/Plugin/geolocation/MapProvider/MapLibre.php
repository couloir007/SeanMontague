<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\MapProvider;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\Attribute\MapProvider;
use Drupal\geolocation\MapProviderBase;
use Drupal\geolocation_maplibre\BaseMapManager;

/**
 * Provides MapLibre GL JS maps.
 */
#[MapProvider(
  id: 'maplibre',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('MapLibre GL'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('MapLibre GL JS support. Renders vector tiles from any style URL.')
)]
class MapLibre extends MapProviderBase {

  /**
   * MapLibre GL JS version to load.
   */
  const LIBRARY_VERSION = '6.9.0';

  /**
   * Base map used when none is configured.
   *
   * OpenFreeMap needs no key and no account, so a map renders - and localizes
   * - out of the box.
   */
  const DEFAULT_BASE_MAP = 'openfreemap';

  /**
   * MapLibre GL JS is an ES module, imported by the provider's own module.
   *
   * Version 6 dropped the UMD build, so there is no global to declare as a
   * script; the provider imports this URL instead.
   */
  const LIBRARY_URL = 'https://unpkg.com/maplibre-gl@' . self::LIBRARY_VERSION . '/dist/maplibre-gl.mjs';

  /**
   * {@inheritdoc}
   */
  protected array $stylesheets = [
    'https://unpkg.com/maplibre-gl@' . self::LIBRARY_VERSION . '/dist/maplibre-gl.css',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return array_replace_recursive(
      parent::getDefaultSettings(),
      [
        'library_url' => self::LIBRARY_URL,
        'base_map_id' => self::DEFAULT_BASE_MAP,
        'base_map_settings' => [],
        'zoom' => 10,
        'min_zoom' => 0,
        'max_zoom' => 20,
        'height' => '400px',
        'width' => '100%',
        'map_features' => [
          'maplibre_control_navigation' => [
            'enabled' => TRUE,
          ],
        ],
        'data_layers' => [
          'geolocation_default_layer:default' => [
            'settings' => [
              'features' => [
                'maplibre_marker_popup' => [
                  'enabled' => TRUE,
                ],
              ],
            ],
          ],
        ],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings): array {
    $settings = parent::getSettings($settings);

    // Zoom arrives from select elements as strings, which MapLibre rejects.
    $settings['zoom'] = (int) $settings['zoom'];
    $settings['min_zoom'] = (int) $settings['min_zoom'];
    $settings['max_zoom'] = (int) $settings['max_zoom'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $settings): array {
    $summary = parent::getSettingsSummary($settings);
    $base_map = static::baseMapManager()->getBaseMap($settings['base_map_id'] ?? self::DEFAULT_BASE_MAP);
    if ($base_map) {
      $summary = array_merge($summary, $base_map->getSettingsSummary($settings['base_map_settings'][$base_map->getPluginId()] ?? []));
    }
    $summary[] = $this->t('Zoom level: @zoom', ['@zoom' => $settings['zoom'] ?? '']);
    $summary[] = $this->t('Height: @height', ['@height' => $settings['height'] ?? '']);
    $summary[] = $this->t('Width: @width', ['@width' => $settings['width'] ?? '']);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], array $context = []): array {
    $settings += self::getDefaultSettings();

    $form = parent::getSettingsForm($settings, $parents, $context);

    $parents_string = $parents ? implode('][', $parents) : NULL;

    $form['base_map'] = [
      '#type' => 'details',
      '#title' => $this->t('Base map'),
      '#open' => TRUE,
      '#weight' => 1,
      'form' => static::baseMapManager()->getOptionsForm($settings, $parents, $this),
    ];

    $form['height'] = [
      '#group' => $parents_string,
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['height'],
    ];
    $form['width'] = [
      '#group' => $parents_string,
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['width'],
    ];
    $form['zoom'] = [
      '#group' => $parents_string,
      '#type' => 'select',
      '#title' => $this->t('Zoom level'),
      '#options' => range(0, 20),
      '#description' => $this->t('The initial resolution at which to display the map, where zoom 0 corresponds to a map of the Earth fully zoomed out, and higher zoom levels zoom in at a higher resolution.'),
      '#default_value' => $settings['zoom'],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'preRenderGroup'],
      ],
    ];
    $form['min_zoom'] = [
      '#group' => $parents_string,
      '#type' => 'select',
      '#title' => $this->t('Minimum zoom'),
      '#options' => range(0, 20),
      '#description' => $this->t('Minimum map zoom level.'),
      '#default_value' => $settings['min_zoom'],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'preRenderGroup'],
      ],
    ];
    $form['max_zoom'] = [
      '#group' => $parents_string,
      '#type' => 'select',
      '#title' => $this->t('Maximum zoom'),
      '#options' => range(0, 20),
      '#description' => $this->t('Maximum map zoom level.'),
      '#default_value' => $settings['max_zoom'],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'preRenderGroup'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array $render_array, array $map_settings = [], array $context = []): array {
    $settings = $this->getMapProviderSettings($map_settings);

    $base_map_id = $settings['base_map_id'] ?? self::DEFAULT_BASE_MAP;
    $base_map_settings = $settings['base_map_settings'] ?? [];
    unset($settings['base_map_id'], $settings['base_map_settings']);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                'maplibre_settings' => $settings,
              ],
            ],
          ],
        ],
      ]
    );

    $render_array = static::baseMapManager()->alterMap($render_array, $base_map_settings, $base_map_id, $this);

    return parent::alterRenderArray($render_array, $map_settings, $context);
  }

  /**
   * Get the base map plugin manager.
   *
   * @return \Drupal\geolocation_maplibre\BaseMapManager
   *   Base map manager.
   */
  protected static function baseMapManager(): BaseMapManager {
    return \Drupal::service('plugin.manager.geolocation_maplibre.basemap');
  }

  /**
   * {@inheritdoc}
   */
  public static function getControlPositions(): array {
    return [
      'top-left' => t('Top left'),
      'top-right' => t('Top right'),
      'bottom-left' => t('Bottom left'),
      'bottom-right' => t('Bottom right'),
    ];
  }

}
