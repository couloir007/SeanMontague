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
      'position' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings, ?MapProviderInterface $mapProvider = NULL): array {
    $settings = parent::getSettings($settings, $mapProvider);

    // Control positions are map provider specific, so settings saved for
    // another provider - or defaults, which cannot know the provider - have to
    // give way to something this provider can actually place.
    $positions = $mapProvider ? $mapProvider->getControlPositions() : [];
    if ($positions && !array_key_exists($settings['position'] ?? '', $positions)) {
      $settings['position'] = array_key_first($positions);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $settings = $this->getSettings($settings, $mapProvider);

    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $mapProvider->getControlPositions(),
      '#default_value' => $settings['position'],
    ];

    return $form;
  }

}
