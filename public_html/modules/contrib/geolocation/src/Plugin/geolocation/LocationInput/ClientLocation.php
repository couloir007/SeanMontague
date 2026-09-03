<?php

namespace Drupal\geolocation\Plugin\geolocation\LocationInput;

use Drupal\geolocation\Attribute\LocationInput;
use Drupal\Core\Url;
use Drupal\geolocation\LocationInputBase;
use Drupal\geolocation\LocationInputInterface;

/**
 * Location based proximity center.
 */
#[LocationInput(
  id: 'client_location',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Client location'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('If client provides location, use it.')
)]
class ClientLocation extends LocationInputBase implements LocationInputInterface {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();

    $settings['auto_submit'] = FALSE;
    $settings['hide_form'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], $context = NULL): array {
    $settings = $this->getSettings($settings);

    $form = parent::getSettingsForm($settings, $context);

    $form['auto_submit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-submit form'),
      '#default_value' => $settings['auto_submit'],
      '#description' => $this->t('Only triggers if location could be set'),
    ];

    $form['hide_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide coordinates form'),
      '#default_value' => $settings['hide_form'],
    ];

    $form['#description'] = $this->t('Location will be set if it is empty and client location is available. This requires a https connection.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array $form, array $settings, array $context = [], ?array $default_value = NULL): array {
    $form = parent::alterForm($form, $settings, $context, $default_value);

    $form['coordinates']['client_location'] = [
      '#type' => 'link',
      '#title' => $this->t('Locate me'),
      '#url' => Url::fromUserInput('#'),
      '#attributes' => [
        'class' => [
          'js-hide',
          'btn',
          'button',
          'geolocation-location-input-client-location',
        ],
      ],
      '#weight' => 10,
    ];

    return $form;
  }

}
