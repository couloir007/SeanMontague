<?php

namespace Drupal\trail_mapper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trail Mapper settings form.
 *
 * Configures the tile layer used by Surface map.js on all Leaflet maps.
 * Settings are passed to map.js via drupalSettings.trailMapper.
 */
class TrailMapperSettingsForm extends ConfigFormBase {

  /**
   * Built-in tile sets.
   */
  protected const TILE_SETS = [
    'usgs-topo' => [
      'label' => 'USGS Topo (default)',
      'url' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}',
      'attribution' => 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
    ],
    'usgs-imagery' => [
      'label' => 'USGS Imagery',
      'url' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}',
      'attribution' => 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
    ],
    'usgs-shaded' => [
      'label' => 'USGS Shaded Relief',
      'url' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSShadedReliefOnly/MapServer/tile/{z}/{y}/{x}',
      'attribution' => 'Tiles &copy; <a href="https://usgs.gov">USGS</a> The National Map',
    ],
    'osm' => [
      'label' => 'OpenStreetMap',
      'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      'attribution' => '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    ],
    'custom' => [
      'label' => 'Custom URL',
      'url' => '',
      'attribution' => '',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['trail_mapper.settings'];
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
    $config = $this->config('trail_mapper.settings');
    $current_key = $config->get('tile_key') ?: 'usgs-topo';

    $options = [];
    foreach (self::TILE_SETS as $key => $tile) {
      $options[$key] = $tile['label'];
    }

    $form['tile_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Tile set'),
      '#description' => $this->t('Background map tiles used on all Leaflet maps.'),
      '#options' => $options,
      '#default_value' => $current_key,
    ];

    $form['custom'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom tile settings'),
      '#open' => $current_key === 'custom',
      '#states' => [
        'visible' => [
          'select[name="tile_key"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['custom']['tile_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tile URL template'),
      '#description' => $this->t('Use {z}, {x}, {y} placeholders. E.g. https://{s}.tile.example.org/{z}/{x}/{y}.png'),
      '#default_value' => $config->get('tile_url') ?: '',
      '#maxlength' => 512,
    ];

    $form['custom']['tile_attribution'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attribution'),
      '#description' => $this->t('HTML attribution string shown in the map corner.'),
      '#default_value' => $config->get('tile_attribution') ?: '',
      '#maxlength' => 512,
    ];

    $form['preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Built-in tile URLs (reference)'),
    ];

    $rows = [];
    foreach (self::TILE_SETS as $key => $tile) {
      if ($key === 'custom') continue;
      $rows[] = [$key, $tile['url']];
    }
    $form['preview']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Key'), $this->t('URL')],
      '#rows' => $rows,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('trail_mapper.settings')
      ->set('tile_key', $form_state->getValue('tile_key'))
      ->set('tile_url', $form_state->getValue('tile_url'))
      ->set('tile_attribution', $form_state->getValue('tile_attribution'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the resolved tile URL for the current settings.
   *
   * Used by hook_page_attachments to pass to drupalSettings.
   *
   * @param string|null $key
   *   Tile key override, or NULL to use saved config.
   *
   * @return array{url: string, attribution: string, key: string}
   */
  public static function resolvedTile(?string $key = NULL): array {
    $config = \Drupal::config('trail_mapper.settings');
    $key = $key ?? $config->get('tile_key') ?? 'usgs-topo';

    if ($key === 'custom') {
      return [
        'key' => 'custom',
        'url' => $config->get('tile_url') ?: self::TILE_SETS['usgs-topo']['url'],
        'attribution' => $config->get('tile_attribution') ?: '',
      ];
    }

    $tile = self::TILE_SETS[$key] ?? self::TILE_SETS['usgs-topo'];
    return [
      'key' => $key,
      'url' => $tile['url'],
      'attribution' => $tile['attribution'],
    ];
  }

}
