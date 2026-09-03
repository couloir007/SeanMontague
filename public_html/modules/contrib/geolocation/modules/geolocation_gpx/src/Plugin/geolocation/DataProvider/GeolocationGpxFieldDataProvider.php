<?php

namespace Drupal\geolocation_gpx\Plugin\geolocation\DataProvider;

use Drupal\geolocation\Attribute\DataProvider;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Provides GPX.
 */
#[DataProvider(id: 'geolocation_gpx',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX Field'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Tracks, Routes & Waypoints.'))]
class GeolocationGpxFieldDataProvider extends DataProviderBase implements DataProviderInterface {

  /**
   * {@inheritdoc}
   */
  protected static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['return_tracks'] = TRUE;
    $settings['return_waypoints'] = TRUE;
    $settings['return_track_locations'] = FALSE;
    $settings['return_waypoint_locations'] = FALSE;
    $settings['track_stroke_color'] = '#FF0044';
    $settings['track_stroke_color_randomize'] = TRUE;
    $settings['track_stroke_width'] = 2;
    $settings['track_stroke_opacity'] = 1;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function isViewsGeoOption(FieldPluginBase $viewsField): bool {
    if (
      $viewsField instanceof EntityField
      && $viewsField->getPluginId() == 'field'
    ) {
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($viewsField->getEntityType());
      if (!empty($field_storage_definitions[$viewsField->field])) {
        $field_storage_definition = $field_storage_definitions[$viewsField->field];

        if ($field_storage_definition->getType() == 'geolocation_gpx') {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $element = parent::getSettingsForm($settings, $parents);

    $settings = $this->getSettings($settings);

    if (!empty($this->viewsField)) {
      $form_parent = "style_options[data_provider_settings]";
    }
    elseif (!empty($this->fieldDefinition)) {
      $form_parent = "fields['" . $this->fieldDefinition->getName() . "'][settings_edit_form][settings]";
    }
    else {
      $form_parent = '';
    }

    $element['return_tracks'] = [
      '#weight' => -99,
      '#type' => 'checkbox',
      '#title' => $this->t('Add tracks'),
      '#description' => $this->t('Will be displayed as polylines; names should show up on hover/click.'),
      '#default_value' => $settings['return_tracks'],
    ];

    $element['return_waypoints'] = [
      '#weight' => -100,
      '#type' => 'checkbox',
      '#title' => $this->t('Add waypoints'),
      '#description' => $this->t('Will be displayed as regular markers, with the name as marker title.'),
      '#default_value' => $settings['return_waypoints'],
    ];

    $element['return_track_locations'] = [
      '#weight' => -100,
      '#type' => 'checkbox',
      '#title' => $this->t('Add raw track locations'),
      '#default_value' => $settings['return_track_locations'],
    ];

    $element['return_waypoint_locations'] = [
      '#weight' => -100,
      '#type' => 'checkbox',
      '#title' => $this->t('Add raw waypoint locations'),
      '#default_value' => $settings['return_waypoint_locations'],
    ];

    $element['track_stroke_color'] = [
      '#weight' => -98,
      '#type' => 'color',
      '#title' => $this->t('Track color'),
      '#default_value' => $settings['track_stroke_color'],
      '#states' => [
        'visible' => [
          ':input[name="' . $form_parent . '[return_tracks]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['track_stroke_color_randomize'] = [
      '#weight' => -98,
      '#type' => 'checkbox',
      '#title' => $this->t('Randomize track colors'),
      '#default_value' => $settings['track_stroke_color_randomize'],
      '#states' => [
        'visible' => [
          ':input[name="' . $form_parent . '[return_tracks]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['track_stroke_width'] = [
      '#weight' => -98,
      '#type' => 'number',
      '#title' => $this->t('Track Width'),
      '#description' => $this->t('Width of the tracks in pixels.'),
      '#default_value' => $settings['track_stroke_width'],
      '#states' => [
        'visible' => [
          ':input[name="' . $form_parent . '[return_tracks]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['track_stroke_opacity'] = [
      '#weight' => -98,
      '#type' => 'number',
      '#step' => 0.01,
      '#title' => $this->t('Track Opacity'),
      '#description' => $this->t('Opacity of the tracks from 1 = fully visible, 0 = complete see through.'),
      '#default_value' => $settings['track_stroke_opacity'],
      '#states' => [
        'visible' => [
          ':input[name="' . $form_parent . '[return_tracks]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getShapesFromItem(FieldItemInterface $fieldItem): array {
    $settings = $this->getSettings();
    if (!$settings['return_tracks']) {
      return [];
    }

    if (empty($fieldItem->getValue()['gpx_id'] ?? NULL)) {
      return [];
    }

    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx|null $gpx */
    $gpx = \Drupal::entityTypeManager()->getStorage('geolocation_gpx')->load($fieldItem->getValue()['gpx_id']);

    if (!$gpx) {
      return [];
    }

    $shapes = [];

    foreach ($gpx->tracks as $track) {
      $geometry = [
        'type' => 'line',
        'points' => [],
      ];
      foreach ($track->entity->track_segments as $segment) {
        foreach ($segment->entity?->track_points as $waypoint) {
          $geometry['points'][] = [
            'lat' => (float) $waypoint->entity?->latitude->value,
            'lng' => (float) $waypoint->entity?->longitude->value,
          ];
        }
      }

      $shapes[] = [
        '#type' => 'geolocation_map_shape',
        '#geometry' => $geometry,
        '#title' => $track->entity->name->value ?? $gpx->name->value,
        '#stroke_color' => $settings['track_stroke_color_randomize'] ? sprintf('#%06X', mt_rand(0, 0xFFFFFF)) : $settings['track_stroke_color'],
        '#stroke_width' => (int) $settings['track_stroke_width'],
        '#stroke_opacity' => (float) $settings['track_stroke_opacity'],
      ];
    }

    foreach ($gpx->routes as $route) {
      $geometry = [
        'type' => 'line',
        'points' => [],
      ];
      foreach ($route->entity->route_points as $waypoint) {
        $geometry['points'][] = [
          'lat' => (float) $waypoint->entity?->latitude->value,
          'lng' => (float) $waypoint->entity?->longitude->value,
        ];
      }

      $shapes[] = [
        '#type' => 'geolocation_map_shape',
        '#geometry' => $geometry,
        // @phpstan-ignore-next-line
        '#title' => $route->entity->name->toString(),
        '#stroke_color' => $settings['track_stroke_color_randomize'] ? sprintf('#%06X', mt_rand(0, 0xFFFFFF)) : $settings['track_stroke_color'],
        '#stroke_width' => (int) $settings['track_stroke_width'],
        '#stroke_opacity' => (float) $settings['track_stroke_opacity'],
      ];
    }

    return $shapes;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsFromItem(FieldItemInterface $fieldItem): array {
    $settings = $this->getSettings();
    if (!$settings['return_waypoints']) {
      return [];
    }

    if (empty($fieldItem->getValue()['gpx_id'] ?? NULL)) {
      return [];
    }

    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx|null $gpx */
    $gpx = \Drupal::entityTypeManager()->getStorage('geolocation_gpx')->load($fieldItem->getValue()['gpx_id']);

    if (!$gpx) {
      return [];
    }

    $positions = [];

    foreach ($gpx->waypoints as $waypoint) {

      $positions[] = [
        '#type' => 'geolocation_map_location',
        '#title' => (string) $waypoint->entity?->name->getString(),
        '#coordinates' => [
          'lat' => (float) $waypoint->entity?->latitude->value,
          'lng' => (float) $waypoint->entity?->longitude->value,
        ],
      ];
    }

    return $positions;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool {
    return ($fieldDefinition->getType() == 'geolocation_gpx');
  }

}
