<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Class ControlMapFeatureBase.
 */
abstract class ControlElementBase extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'position' => 'TOP_LEFT',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $settings = parent::getSettings($settings);

    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $mapProvider->getControlPositions(),
      '#default_value' => $settings['position'],
    ];

    return $form;
  }

}
