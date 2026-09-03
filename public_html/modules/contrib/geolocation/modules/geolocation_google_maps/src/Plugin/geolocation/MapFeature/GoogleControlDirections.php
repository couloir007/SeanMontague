<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\Component\Utility\Html;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Directions Service.
 */
#[MapFeature(
  id: 'geolocation_google_maps_control_directions',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Directions'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Integrate direction finder.'),
  type: 'google_maps'
)]
class GoogleControlDirections extends GoogleControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();

    $settings['origin_source'] = 'exposed';
    $settings['origin_static_value'] = '';

    $settings['destination_source'] = 'exposed';
    $settings['destination_static_value'] = '';

    $settings['travel_mode'] = 'exposed';

    $settings['directions_container'] = 'below';
    $settings['directions_container_custom_id'] = '';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $states_prefix = array_shift($parents) . '[' . implode('][', $parents) . ']';

    $form['origin_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Origin source'),
      '#options' => [
        'exposed' => $this->t('Exposed textfield for user.'),
        'static' => $this->t('Static value'),
      ],
      '#description' => $this->t('Origin point for directions.'),
      '#default_value' => $settings['origin_source'],
    ];

    $form['origin_static_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Static origin'),
      '#description' => $this->t('Enter an address or coordinates as "lat, lng". Tokens supported.'),
      '#default_value' => $settings['origin_static_value'],
      '#states' => [
        'visible' => [
          'select[name="' . $states_prefix . '[origin_source]"]' => ['value' => 'static'],
        ],
      ],
    ];

    $form['destination_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination source'),
      '#options' => [
        'exposed' => $this->t('Exposed textfield for user.'),
        'static' => $this->t('Static value'),
      ],
      '#description' => $this->t('Destination point for directions.'),
      '#default_value' => $settings['destination_source'],
    ];

    $form['destination_static_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Static destination'),
      '#description' => $this->t('Enter an address or coordinates as "lat, lng". Tokens supported.'),
      '#default_value' => $settings['destination_static_value'],
      '#states' => [
        'visible' => [
          'select[name="' . $states_prefix . '[destination_source]"]' => ['value' => 'static'],
        ],
      ],
    ];

    $form['travel_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Travel mode'),
      '#options' => [
        'exposed' => $this->t('Exposed'),
        'driving' => $this->t('Driving'),
        'walking' => $this->t('Walking'),
        'bicycling' => $this->t('Bicycling'),
        'transit' => $this->t('Transit'),
      ],
      '#default_value' => $settings['travel_mode'],
    ];

    $form['directions_container'] = [
      '#type' => 'select',
      '#title' => $this->t('Directions container'),
      '#options' => [
        'above' => $this->t('Attach above map.'),
        'below' => $this->t('Attach below map'),
        'custom' => $this->t('Inject to custom Element by #ID'),
      ],
      '#default_value' => $settings['directions_container'],
    ];

    $form['directions_container_custom_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directions custom container #ID'),
      '#default_value' => $settings['directions_container_custom_id'],
      '#states' => [
        'visible' => [
          'select[name="' . $states_prefix . '[directions_container]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Directions control form.
   *
   * @param array $settings
   *   Settings.
   * @param array $context
   *   Context.
   *
   * @return array
   *   Directions form.
   */
  public function getDirectionsControlForm(array $settings, array $context = []): array {
    $form = [
      '#type' => 'form',
      '#form_id' => 'directions_control',
      '#attributes' => [
        'id' => Html::getUniqueId('geolocation-google-maps-directions-controls'),
        'class' => [
          'geolocation-google-maps-directions-controls',
          'hidden',
        ],
      ],
    ];

    if (
      $settings['origin_source'] == 'exposed'
      || $settings['destination_source'] == 'exposed'
    ) {
      $form['#attributes']['class'][] = 'geolocation-google-maps-directions-controls-block';
    }

    switch ($settings['origin_source']) {
      case 'exposed':
        $form['origin'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Origin'),
          '#size' => 24,
          '#name' => 'geolocation-google-maps-directions-controls-origin',
          '#description' => $this->t('Enter an address like "Chicago, IL".'),
          '#description_display' => 'after',
        ];
        break;

      case 'static':
        $form['origin'] = [
          '#type' => 'hidden',
          '#name' => 'geolocation-google-maps-directions-controls-origin',
          '#value' => $this->token->replace($settings['origin_static_value'], $context),
        ];
        break;
    }

    switch ($settings['destination_source']) {
      case 'exposed':
        $form['destination'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Destination'),
          '#size' => 24,
          '#name' => 'geolocation-google-maps-directions-controls-destination',
          '#description' => $this->t('Enter an address like "Darwin, NSW, Australia".'),
          '#description_display' => 'after',
        ];
        break;

      case 'static':
        $form['destination'] = [
          '#type' => 'hidden',
          '#name' => 'geolocation-google-maps-directions-controls-destination',
          '#value' => $this->token->replace($settings['destination_static_value'], $context),
        ];
        break;
    }

    $form['travel_mode'] = match ($settings['travel_mode']) {
      'exposed' => [
        '#type' => 'select',
        '#title' => $this->t('Origin'),
        '#options' => [
          'driving' => $this->t('Driving'),
          'walking' => $this->t('Walking'),
          'bicycling' => $this->t('Bicycling'),
          'transit' => $this->t('Transit'),
        ],
        '#name' => 'geolocation-google-maps-directions-controls-travel-mode',
        '#description_display' => 'after',
      ],
      default => [
        '#type' => 'hidden',
        '#name' => 'geolocation-google-maps-directions-controls-travel-mode',
        '#value' => $settings['travel_mode'],
      ],
    };

    $form['get_directions'] = [
      '#type' => 'button',
      '#value' => $this->t('Get Directions'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls']['directions'] = $this->getDirectionsControlForm($feature_settings, $context);

    switch ($feature_settings['directions_container']) {
      case 'below':
      case 'above':
        $render_array['directions_container'] = [
          '#markup' => '<div class="geolocation-google-maps-directions-container"></div>',
          '#weight' => $feature_settings['directions_container'] === 'below' ? -1000 : 1000,
        ];
        break;
    }

    return $render_array;
  }

}
