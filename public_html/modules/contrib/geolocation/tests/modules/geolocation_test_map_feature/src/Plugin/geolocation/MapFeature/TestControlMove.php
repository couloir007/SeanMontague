<?php

namespace Drupal\geolocation_test_map_feature\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlCustomElementBase;

/**
 * Button moving the map to fixed boundaries.
 *
 * Stands in for features like geocoding or client location, which move the
 * map on user request, but cannot be used in tests.
 */
#[MapFeature(
  id: 'test_control_move',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Test - Move map'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Button moving the map to fixed boundaries.'),
  type: 'all'
)]
class TestControlMove extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return parent::getDefaultSettings() + [
      'north' => 0,
      'east' => 0,
      'south' => 0,
      'west' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId]['test_control_move'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Move map',
      '#attributes' => [
        'class' => ['test-move'],
      ],
    ];

    return $render_array;
  }

}
