<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Google Maps.
 */
#[LayerFeature(id: 'marker_label',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Label Adjustment'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Label properties.'), type: 'google_maps')]
class GoogleMarkerLabel extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'color' => '',
      'font_family' => '',
      'font_size' => '',
      'font_weight' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color'),
      '#description' => $this->t('The color of the label text. Default color is black.'),
      '#default_value' => $settings['color'],
    ];

    $form['font_family'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Family'),
      '#description' => $this->t('The font family of the label text (equivalent to the CSS font-family property).'),
      '#default_value' => $settings['font_family'],
    ];

    $form['font_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Size'),
      '#description' => $this->t('The font size of the label text (equivalent to the CSS font-size property). Default size is 14px.'),
      '#default_value' => $settings['font_size'],
    ];

    $form['font_weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Weight'),
      '#description' => $this->t('The font weight of the label text (equivalent to the CSS font-weight property).'),
      '#default_value' => $settings['font_weight'],
    ];

    return $form;
  }

}
