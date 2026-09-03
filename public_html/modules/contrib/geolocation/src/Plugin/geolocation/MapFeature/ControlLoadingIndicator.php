<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Recenter control element.
 */
#[MapFeature(
  id: 'control_loading_indicator',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Loading Indicator'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('When using an interactive map, shows a loading icon and label if there is currently data fetched from the backend via AJAX.'),
  type: 'all',
)]
class ControlLoadingIndicator extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['loading_label'] = 'Loading';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['loading_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Shown during loading.'),
      '#default_value' => $settings['loading_label'],
      '#size' => 20,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId]['control_loading_indicator'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $feature_settings['loading_label'],
      '#attributes' => [
        'class' => [
          'loading-indicator',
          'hidden',
        ],
      ],
    ];

    return $render_array;
  }

}
