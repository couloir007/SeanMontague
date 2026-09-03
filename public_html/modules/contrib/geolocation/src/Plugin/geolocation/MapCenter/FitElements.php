<?php

namespace Drupal\geolocation\Plugin\geolocation\MapCenter;

use Drupal\geolocation\Attribute\MapCenter;
use Drupal\geolocation\MapCenterBase;
use Drupal\geolocation\MapCenterInterface;

/**
 * Fixed coordinates map center.
 *
 * ID for compatibility with v1.
 */
#[MapCenter(
  id: 'fit_bounds',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Fit Elements (markers & shapes)'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Automatically fit map to displayed elements.')
)]
class FitElements extends MapCenterBase implements MapCenterInterface {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'min_zoom' => 12,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(?string $option_id = NULL, array $settings = [], array $context = []): array {
    $form = parent::getSettingsForm($option_id, $settings, $context);
    $form['min_zoom'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Set a minimum zoom, especially useful when only location is centered on.'),
      '#default_value' => $settings['min_zoom'],
    ];

    return $form;
  }

}
