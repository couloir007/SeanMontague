<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Provides the MapLibre scale control.
 */
#[MapFeature(
  id: 'maplibre_control_scale',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Scale'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add a scale bar to the map.'),
  type: 'maplibre'
)]
class MapLibreControlScale extends ControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return parent::getDefaultSettings() + [
      'unit' => 'metric',
      'max_width' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $settings = $this->getSettings($settings, $mapProvider);

    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#options' => [
        'metric' => $this->t('Metric'),
        'imperial' => $this->t('Imperial'),
        'nautical' => $this->t('Nautical'),
      ],
      '#default_value' => $settings['unit'],
    ];

    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum length of the scale bar, in pixels.'),
      '#default_value' => $settings['max_width'],
      '#min' => 20,
    ];

    return $form;
  }

}
