<?php

namespace Drupal\geolocation_geometry\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\Plugin\geolocation\MapFeature\GeolocationFieldWidgetMapConnector;

/**
 * Base class for the Terra Draw based geometry widget connectors.
 *
 * Carries what they share: the library, the adapter per map provider and the
 * drawing control. How drawn geometries map onto field items is left to the
 * implementing connector.
 */
abstract class GeolocationGeometryTerraDrawConnectorBase extends GeolocationFieldWidgetMapConnector {

  /**
   * Terra Draw adapters, keyed by map provider ID.
   *
   * Terra Draw talks to the map through an adapter, of which it ships one per
   * supported mapping library. Map providers absent here have no adapter and
   * cannot be drawn on.
   *
   * The UMD builds expose the adapter class on a global of their own, and
   * resolve their own dependencies from globals as well - "globals" aliases
   * those to the name the respective library actually exports.
   *
   * @var array<string, array<string, mixed>>
   */
  protected const ADAPTERS = [
    'google_maps' => [
      'script' => 'https://cdn.jsdelivr.net/npm/terra-draw-google-maps-adapter@1.6.1/dist/terra-draw-google-maps-adapter.umd.js',
      'global' => 'terraDrawGoogleMapsAdapter',
      'class' => 'TerraDrawGoogleMapsAdapter',
      'globals' => [],
    ],
    'leaflet' => [
      'script' => 'https://cdn.jsdelivr.net/npm/terra-draw-leaflet-adapter@1.3.0/dist/terra-draw-leaflet-adapter.umd.js',
      'global' => 'terraDrawLeafletAdapter',
      'class' => 'TerraDrawLeafletAdapter',
      'globals' => ['leaflet' => 'L'],
    ],
    'maplibre' => [
      'script' => 'https://cdn.jsdelivr.net/npm/terra-draw-maplibre-gl-adapter@1.4.1/dist/terra-draw-maplibre-gl-adapter.umd.js',
      'global' => 'terraDrawMaplibreGlAdapter',
      'class' => 'TerraDrawMapLibreGLAdapter',
      'globals' => [],
    ],
  ];

  /**
   * Terra Draw drawing mode by geometry field type.
   *
   * Terra Draw draws single geometries only - its store holds Point, LineString
   * and Polygon - so a multi field type draws the parts its geometry is made
   * of, in the mode of those parts.
   *
   * "mode" is Terra Draw's own name for it, under which its store also records
   * what a feature was drawn in. "class" is the mode's export on the library
   * global, which does not follow from the mode name. "geojson_type" is what a
   * feature drawn in that mode holds.
   *
   * @var array<string, array<string, string>>
   */
  protected const DRAWING_MODES = [
    'geolocation_geometry_point' => [
      'mode' => 'point',
      'class' => 'TerraDrawPointMode',
      'geojson_type' => 'Point',
    ],
    'geolocation_geometry_multipoint' => [
      'mode' => 'point',
      'class' => 'TerraDrawPointMode',
      'geojson_type' => 'Point',
    ],
    'geolocation_geometry_linestring' => [
      'mode' => 'linestring',
      'class' => 'TerraDrawLineStringMode',
      'geojson_type' => 'LineString',
    ],
    'geolocation_geometry_multilinestring' => [
      'mode' => 'linestring',
      'class' => 'TerraDrawLineStringMode',
      'geojson_type' => 'LineString',
    ],
    'geolocation_geometry_polygon' => [
      'mode' => 'polygon',
      'class' => 'TerraDrawPolygonMode',
      'geojson_type' => 'Polygon',
    ],
    'geolocation_geometry_multipolygon' => [
      'mode' => 'polygon',
      'class' => 'TerraDrawPolygonMode',
      'geojson_type' => 'Polygon',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected array $scripts = [
    'https://cdn.jsdelivr.net/npm/terra-draw@1.33.0/dist/terra-draw.umd.js',
  ];

  /**
   * Get the Terra Draw adapter definition for a map provider.
   *
   * @param string|null $map_provider_id
   *   Map provider ID.
   *
   * @return array<string, mixed>|null
   *   Adapter definition, or NULL if Terra Draw has no adapter for it.
   */
  public static function getAdapter(?string $map_provider_id): ?array {
    if (!$map_provider_id || !isset(static::ADAPTERS[$map_provider_id])) {
      return NULL;
    }

    return static::ADAPTERS[$map_provider_id] + ['map_provider' => $map_provider_id];
  }

  /**
   * Get the Terra Draw drawing mode for a geometry field type.
   *
   * @param string|null $field_type
   *   Field type.
   *
   * @return array<string, string>|null
   *   Drawing mode definition, or NULL for a field type Terra Draw cannot
   *   draw.
   */
  public static function getDrawingMode(?string $field_type): ?array {
    if (!$field_type || !isset(static::DRAWING_MODES[$field_type])) {
      return NULL;
    }

    return static::DRAWING_MODES[$field_type];
  }

  /**
   * Get the map providers Terra Draw ships an adapter for.
   *
   * @return string[]
   *   Map provider IDs.
   */
  public static function getSupportedMapProviders(): array {
    return array_keys(static::ADAPTERS);
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    // The control looks the same for every Terra Draw connector, so one
    // stylesheet serves them all rather than one named after each concrete
    // class, which is all the parent would pick up by itself.
    $stylesheet = base_path() . $this->moduleHandler->getModule('geolocation_geometry')->getPath() . '/css/MapFeature/GeolocationGeometryTerraDraw.css';
    if (!in_array($stylesheet, $this->stylesheets, TRUE)) {
      $this->stylesheets[] = $stylesheet;
    }

    $adapter = static::getAdapter($mapProvider?->getPluginId());
    // Which mode to draw in follows from the field type, which only the widget
    // knows - so the map is told, rather than working it out for itself.
    $drawing_mode = static::getDrawingMode($feature_settings['field_type'] ?? NULL);

    $feature_settings['adapter'] = $adapter;
    $feature_settings['drawing_mode'] = $drawing_mode;
    // The control markup is keyed on the plugin ID, so the connector can find
    // its own control among those of any other feature on the map.
    $feature_settings['plugin_id'] = $this->getPluginId();

    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'geolocation-map-control',
          'hidden',
          'terra-draw-control',
          $this->pluginId,
        ],
      ],
    ];

    if (!$adapter || !$drawing_mode) {
      $render_array['#controls'][$this->pluginId]['warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $drawing_mode
          ? $this->t('Drawing is unavailable: Terra Draw provides no adapter for the %provider map.', [
            '%provider' => $mapProvider?->getPluginDefinition()['name'] ?? $this->t('selected'),
          ])
          : $this->t('Drawing is unavailable: Terra Draw cannot draw geometries of type %type.', [
            '%type' => $feature_settings['field_type'] ?? $this->t('unknown'),
          ]),
        '#attributes' => [
          'class' => ['terra-draw-warning'],
        ],
      ];

      return $render_array;
    }

    $render_array['#controls'][$this->pluginId]['draw'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Draw'),
      '#attributes' => [
        'type' => 'button',
        'class' => ['terra-draw-mode'],
        'data-terra-draw-mode' => 'draw',
      ],
    ];

    $render_array['#controls'][$this->pluginId]['select'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Edit'),
      '#attributes' => [
        'type' => 'button',
        'class' => ['terra-draw-mode'],
        'data-terra-draw-mode' => 'select',
      ],
    ];

    $render_array['#controls'][$this->pluginId]['delete'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Delete'),
      '#attributes' => [
        'type' => 'button',
        'class' => ['terra-draw-delete'],
        'disabled' => 'disabled',
      ],
    ];

    $render_array['#controls'][$this->pluginId]['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => '',
      '#attributes' => [
        'class' => ['terra-draw-message'],
        'aria-live' => 'polite',
      ],
    ];

    return $render_array;
  }

}
