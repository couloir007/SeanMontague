<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Recenter control element.
 */
#[MapFeature(
  id: 'control_recenter',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Recenter'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add button to recenter map.'),
  type: 'all',
)]
class ControlCustomRecenter extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId]['control_recenter'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Recenter'),
      '#attributes' => [
        'class' => ['recenter'],
      ],
    ];

    return $render_array;
  }

}
