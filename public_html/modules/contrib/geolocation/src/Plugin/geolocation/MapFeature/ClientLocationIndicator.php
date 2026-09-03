<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Redraw locations as shapes.
 */
#[MapFeature(
  id: 'client_location_indicator',
  name: new TranslatableMarkup('Client Location Indicator'),
  description: new TranslatableMarkup('Show and permanently update client location.'),
  type: 'all'
)]
class ClientLocationIndicator extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['icon_path'] = \Drupal::service('extension.list.module')->getPath('geolocation') . '/icons/current_location.png';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['icon_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon path'),
      '#default_value' => $settings['icon_path'],
      '#description' => $this->t('Path to icon. Empty to default.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings, ?MapProviderInterface $mapProvider = NULL): array {
    $settings = parent::getSettings($settings, $mapProvider);

    $settings['icon_path'] = $settings['icon_path'] ?: self::getDefaultSettings()['icon_path'];

    return $settings;
  }

}
