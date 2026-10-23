<?php

namespace Drupal\geolocation_yandex\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides marker popups.
 */
#[LayerFeature(
  id: 'yandex_marker_popup',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Popup'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Open a popup when a marker is clicked.'),
  type: 'yandex'
)]
class YandexMarkerPopup extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'info_auto_display' => FALSE,
      'position' => 'top',
      'offset' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['info_auto_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open every popup as soon as the map loads.'),
      '#default_value' => $settings['info_auto_display'],
    ];

    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position of the popup relative to the marker.'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $settings['position'],
    ];

    $form['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Distance between the popup and the marker, in pixels.'),
      '#default_value' => $settings['offset'],
      '#min' => 0,
    ];

    return $form;
  }

}
