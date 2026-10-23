<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Provides the MapLibre navigation control.
 */
#[MapFeature(
  id: 'maplibre_control_navigation',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Navigation'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add buttons to zoom the map, and a compass to reset its bearing and pitch.'),
  type: 'maplibre'
)]
class MapLibreControlNavigation extends ControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return parent::getDefaultSettings() + [
      'show_zoom' => TRUE,
      'show_compass' => TRUE,
      'visualize_pitch' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $settings = $this->getSettings($settings, $mapProvider);

    $form['show_zoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the zoom buttons.'),
      '#default_value' => $settings['show_zoom'],
    ];

    $form['show_compass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the compass, which resets the bearing when clicked.'),
      '#default_value' => $settings['show_compass'],
    ];

    $form['visualize_pitch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tilt the compass to show the pitch of the map.'),
      '#default_value' => $settings['visualize_pitch'],
      '#states' => [
        'visible' => [
          ':input[name$="[show_compass]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

}
