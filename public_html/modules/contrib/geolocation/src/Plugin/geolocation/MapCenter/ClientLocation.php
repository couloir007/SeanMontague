<?php

namespace Drupal\geolocation\Plugin\geolocation\MapCenter;

use Drupal\geolocation\Attribute\MapCenter;
use Drupal\geolocation\MapCenterBase;
use Drupal\geolocation\MapCenterInterface;

/**
 * Fixed boundaries map center.
 */
#[MapCenter(
  id: 'client_location',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Client Location'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('If client allows, set location. Optionally follow continously.')
)]
class ClientLocation extends MapCenterBase implements MapCenterInterface {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'initial_latitude' => 0,
      'initial_longitude' => 0,
      'follow_client' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(?string $option_id = NULL, array $settings = [], array $context = []): array {
    $form = parent::getSettingsForm($option_id, $settings, $context);
    $settings = $this->getSettings($settings);

    $form['initial_latitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial Latitude'),
      '#default_value' => $settings['initial_latitude'] ?? 0,
      '#description' => $this->t('Before client location becomes available, if at all.'),
      '#size' => 60,
      '#maxlength' => 128,
    ];

    $form['initial_longitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial Longitude'),
      '#default_value' => $settings['initial_longitude'] ?? 0,
      '#description' => $this->t('Before client location becomes available, if at all.'),
      '#size' => 60,
      '#maxlength' => 128,
    ];

    $form['follow_client'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Follow client location'),
      '#default_value' => $settings['follow_client'],
      '#description' => $this->t('Continuously update center by client location if available.'),
    ];

    return $form;
  }

}
