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
      ->save();

    parent::submitForm($form, $form_state);
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

    $tiles = [
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
    ];

    if ($key === 'custom') {
      return [
        'key'         => 'custom',
        'url'         => $config->get('tile_url') ?? '',
        'attribution' => $config->get('tile_attribution') ?? '',
        'maxZoom'     => 16,
      ];
    }

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

}
