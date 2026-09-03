<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides marker infowindow.
 */
#[LayerFeature(id: 'marker_infowindow',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker InfoWindow'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Open InfoWindow on Marker click.'), type: 'google_maps')]
class GoogleMarkerInfoWindow extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'info_auto_display' => FALSE,
      'disable_auto_pan' => TRUE,
      'info_window_solitary' => TRUE,
      'max_width' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['info_window_solitary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only allow one current open info window.'),
      '#description' => $this->t('If checked, clicking a marker will close the current info window before opening a new one.'),
      '#default_value' => $settings['info_window_solitary'],
    ];

    $form['info_auto_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically show info text.'),
      '#default_value' => $settings['info_auto_display'],
    ];
    $form['disable_auto_pan'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable automatic panning of map when info bubble is opened.'),
      '#default_value' => $settings['disable_auto_pan'],
    ];
    $form['max_width'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Max width in pixel. 0 to ignore.'),
      '#default_value' => $settings['max_width'],
    ];

    return $form;
  }

}
