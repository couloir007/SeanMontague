<?php

namespace Drupal\trail_mapper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form for Trail Mapper.
 *
 * Manages tile set selection and elevation display unit.
 * Settings are exposed to JS via hook_page_attachments as
 * drupalSettings.trailMapper.
 */
class TrailMapperSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'trail_mapper.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'trail_mapper_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    $form['tile_key'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Tile set'),
      '#default_value' => $config->get('tile_key') ?? 'usgs-topo',
      '#options'       => [
        'usgs-topo'         => $this->t('USGS Topo'),
        'usgs-imagery'      => $this->t('USGS Imagery'),
        'usgs-imagery-topo' => $this->t('USGS Imagery + Topo'),
        'usgs-shaded'       => $this->t('USGS Shaded Relief'),
        'osm'               => $this->t('OpenStreetMap'),
        'open-topo'         => $this->t('OpenTopoMap'),
        'esri-topo'         => $this->t('Esri World Topo'),
        'custom'            => $this->t('Custom URL'),
      ],
    ];

    $form['tile_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Custom tile URL'),
      '#default_value' => $config->get('tile_url') ?? '',
      '#description'   => $this->t('Leaflet tile URL template. Required when Tile set is Custom URL.'),
      '#states'        => [
        'visible' => [':input[name="tile_key"]' => ['value' => 'custom']],
      ],
    ];

    $form['tile_attribution'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Custom tile attribution'),
      '#default_value' => $config->get('tile_attribution') ?? '',
      '#states'        => [
        'visible' => [':input[name="tile_key"]' => ['value' => 'custom']],
      ],
    ];

    $form['elevation_unit'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Elevation display unit'),
      '#default_value' => $config->get('elevation_unit') ?? 'feet',
      '#options'       => [
        'feet'   => $this->t('Feet'),
        'meters' => $this->t('Meters'),
      ],
      '#description'   => $this->t('Unit used in the stats bar and elevation profile. GeoJSON is always stored in meters.'),
    ];

    $form['extract_waypoints'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Extract waypoints from GPX'),
      '#default_value' => $config->get('extract_waypoints') ?? FALSE,
      '#description'   => $this->t('When a GPX file is uploaded, automatically create POI geo entities from any &lt;wpt&gt; elements found in the file. Skips waypoints whose coordinates match an existing POI within 0.001°.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(self::CONFIG_NAME)
      ->set('tile_key', $form_state->getValue('tile_key'))
      ->set('tile_url', $form_state->getValue('tile_url'))
      ->set('tile_attribution', $form_state->getValue('tile_attribution'))
      ->set('elevation_unit', $form_state->getValue('elevation_unit'))
      ->set('extract_waypoints', (bool) $form_state->getValue('extract_waypoints'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns all registered tile set definitions.
   *
   * @return array
   *   Keyed by tile set key; each entry has url, attribution, maxZoom.
   */
  public static function tileSets(): array {
    return [
      'usgs-topo' => [
        'url'         => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}',
        'attribution' => 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
        'maxZoom'     => 16,
      ],
      'usgs-imagery' => [
        'url'         => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}',
        'attribution' => 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
        'maxZoom'     => 16,
      ],
      'usgs-imagery-topo' => [
        'url'         => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryTopo/MapServer/tile/{z}/{y}/{x}',
        'attribution' => 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
        'maxZoom'     => 16,
      ],
      'usgs-shaded' => [
        'url'         => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSShadedReliefOnly/MapServer/tile/{z}/{y}/{x}',
        'attribution' => 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
        'maxZoom'     => 16,
      ],
      'osm' => [
        'url'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        'maxZoom'     => 19,
      ],
      'open-topo' => [
        'url'         => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
        'maxZoom'     => 17,
      ],
      'esri-topo' => [
        'url'         => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
        'attribution' => '&copy; <a href="https://www.esri.com">Esri</a>',
        'maxZoom'     => 18,
      ],
    ];
  }

  /**
   * Returns all tile set definitions (alias for tileSets()).
   *
   * @return array
   *   Keyed by tile set key; each entry has url, attribution, maxZoom.
   */
  public static function allTileSets(): array {
    return self::tileSets();
  }

  /**
   * Returns resolved tile config for use in hook_page_attachments.
   *
   * @return array
   *   Array with keys: key, url, attribution, maxZoom.
   */
  public static function resolvedTile(): array {
    $config = \Drupal::config(self::CONFIG_NAME);
    $key = $config->get('tile_key') ?? 'usgs-topo';

    if ($key === 'custom') {
      return [
        'key'         => 'custom',
        'url'         => $config->get('tile_url') ?? '',
        'attribution' => $config->get('tile_attribution') ?? '',
        'maxZoom'     => 16,
      ];
    }

    $tiles = self::tileSets();
    $tile = $tiles[$key] ?? $tiles['usgs-topo'];
    return array_merge(['key' => $key], $tile);
  }

  /**
   * Returns the configured elevation unit.
   *
   * @return string
   *   'feet' or 'meters'.
   */
  public static function elevationUnit(): string {
    return \Drupal::config(self::CONFIG_NAME)->get('elevation_unit') ?? 'feet';
  }

  /**
   * Returns whether GPX waypoint extraction is enabled.
   *
   * @return bool
   */
  public static function extractWaypointsEnabled(): bool {
    return (bool) (\Drupal::config(self::CONFIG_NAME)->get('extract_waypoints') ?? FALSE);
  }

}
