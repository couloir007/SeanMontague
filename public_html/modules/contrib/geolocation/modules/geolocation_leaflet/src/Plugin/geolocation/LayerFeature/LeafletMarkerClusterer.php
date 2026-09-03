<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides marker clusterer.
 */
#[LayerFeature(
  id: 'leaflet_marker_clusterer',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Clusterer'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Cluster close markers together.'),
  type: 'leaflet'
)]
class LeafletMarkerClusterer extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  protected array $scripts = [
    'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',
  ];

  /**
   * {@inheritdoc}
   */
  protected array $stylesheets = [
    'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
    'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $default_settings = parent::getDefaultSettings();

    $default_settings['cluster_settings'] = [
      'show_coverage_on_hover' => TRUE,
      'zoom_to_bounds_on_click' => TRUE,
    ];
    $default_settings['disable_clustering_at_zoom'] = 0;
    $default_settings['custom_marker_settings'] = '';

    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $options = [
      'show_coverage_on_hover' => $this->t('Hovering over a cluster shows the bounds of its markers.'),
      'zoom_to_bounds_on_click' => $this->t('Clicking a cluster zooms to the bounds.'),
    ];

    $form['cluster_settings'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Marker Cluster default settings'),
      '#default_value' => array_keys(array_filter($settings['cluster_settings'])),
    ];

    $form['disable_clustering_at_zoom'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 20,
      '#step' => 1,
      '#size' => 4,
      '#title' => $this->t('Disable clustering at zoom'),
      '#description' => $this->t('If set, at this zoom level and below, markers will not be clustered. 0 is off.'),
      '#default_value' => $settings['disable_clustering_at_zoom'],
    ];

    $form['custom_marker_settings'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Custom marker settings in JSON format like: {"small": {"radius": 40, "limit": 10}, "medium": {"radius": 60, "limit": 50}}.'),
      '#default_value' => $settings['custom_marker_settings'],
    ];

    return $form;
  }

}
