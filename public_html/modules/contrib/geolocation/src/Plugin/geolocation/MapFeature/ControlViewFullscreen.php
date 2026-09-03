<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Recenter control element.
 */
#[MapFeature(
  id: 'control_view_fullscreen',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - View Fullscreen'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Trigger Fullscreen on entire View container.'),
  type: 'all',
)]
class ControlViewFullscreen extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId]['control_view_fullscreen'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Fullscreen'),
      '#attributes' => [
        'title' => $this->t('Fullscreen'),
        'class' => [
          'geolocation-control-view-fullscreen',
        ],
      ],
    ];

    return $render_array;
  }

}
