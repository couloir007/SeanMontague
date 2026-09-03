<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides MapType control element.
 */
#[MapFeature(
  id: 'control_maptype',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Control - MapType'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Add button to toggle map type.'),
  type: 'google_maps'
)]
class GoogleControlMapType extends GoogleControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['style'] = 'DEFAULT';
    $settings['position'] = 'RIGHT_BOTTOM';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => [
        'DEFAULT' => $this->t('Default (Map size dependent)'),
        'HORIZONTAL_BAR' => $this->t('Horizontal Bar'),
        'DROPDOWN_MENU' => $this->t('Dropdown Menu'),
      ],
      '#default_value' => $settings['style'],
    ];

    return $form;
  }

}
