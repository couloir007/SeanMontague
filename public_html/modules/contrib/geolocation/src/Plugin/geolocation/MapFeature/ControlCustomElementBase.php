<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapProviderInterface;

/**
 * Class ControlMapFeatureBase.
 */
abstract class ControlCustomElementBase extends ControlElementBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'geolocation-map-control',
          'hidden',
          $this->pluginId,
        ],
        'data-map-control-position' => $feature_settings['position'],
      ],
    ];

    return $render_array;
  }

}
