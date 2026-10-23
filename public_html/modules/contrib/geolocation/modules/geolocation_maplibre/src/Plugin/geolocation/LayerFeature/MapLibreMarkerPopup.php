<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides marker popups.
 */
#[LayerFeature(
  id: 'maplibre_marker_popup',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Popup'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Open a popup when a marker is clicked.'),
  type: 'maplibre'
)]
class MapLibreMarkerPopup extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'info_auto_display' => FALSE,
      'max_width' => 300,
      'close_button' => TRUE,
      'close_on_click' => TRUE,
      'close_on_move' => FALSE,
      'class_name' => '',
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

    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum width of the popup, in pixels.'),
      '#default_value' => $settings['max_width'],
      '#min' => 0,
    ];

    $form['close_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a close button in the popup.'),
      '#default_value' => $settings['close_button'],
    ];

    $form['close_on_click'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close the popup when the map is clicked.'),
      '#default_value' => $settings['close_on_click'],
    ];

    $form['close_on_move'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close the popup when the map is moved.'),
      '#default_value' => $settings['close_on_move'],
    ];

    $form['class_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('A custom CSS class to add to the popup.'),
      '#default_value' => $settings['class_name'],
    ];

    return $form;
  }

}
