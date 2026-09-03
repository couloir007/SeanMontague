<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Google Maps.
 */
#[MapFeature(
  id: 'map_restriction',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Restriction'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Restrict map to set bounds.'),
  type: 'google_maps'
)]
class GoogleMapRestriction extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'north' => '',
      'south' => '',
      'east' => '',
      'west' => '',
      'strict' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['north'] = [
      '#type' => 'textfield',
      '#title' => $this->t('North'),
      '#size' => 15,
      '#default_value' => $settings['north'],
    ];
    $form['south'] = [
      '#type' => 'textfield',
      '#title' => $this->t('South'),
      '#size' => 15,
      '#default_value' => $settings['south'],
    ];
    $form['east'] = [
      '#type' => 'textfield',
      '#title' => $this->t('East'),
      '#size' => 15,
      '#default_value' => $settings['east'],
    ];
    $form['west'] = [
      '#type' => 'textfield',
      '#title' => $this->t('West'),
      '#size' => 15,
      '#default_value' => $settings['west'],
    ];
    $form['strict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('strictBounds'),
      '#default_value' => $settings['strict'],
    ];

    return $form;
  }

}
