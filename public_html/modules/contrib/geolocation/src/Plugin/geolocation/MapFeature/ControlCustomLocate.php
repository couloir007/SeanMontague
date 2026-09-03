<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Locate control element.
 */
#[MapFeature(
  id: 'control_locate',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - Locate'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add button to center on client location. Hidden on non-https connection.'),
  type: 'all'
)]
class ControlCustomLocate extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId]['control_locate'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Locate'),
      '#attributes' => [
        'class' => ['locate'],
      ],
    ];

    return $render_array;
  }

}
