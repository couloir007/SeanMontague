<?php

namespace Drupal\geolocation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Plugin implementation of the 'geolocation_map' widget.
 */
#[FieldWidget(
  id: 'geolocation_map',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Map'),
  field_types: ['geolocation']
)]
class GeolocationMapWidget extends GeolocationMapWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $default_field_values = FALSE;

    if (!empty($this->fieldDefinition->getDefaultValueLiteral()[$delta])) {
      $default_field_values = [
        'lat' => $this->fieldDefinition->getDefaultValueLiteral()[$delta]['lat'],
        'lng' => $this->fieldDefinition->getDefaultValueLiteral()[$delta]['lng'],
      ];
    }

    // '0' is an allowed value, '' is not.
    if (
      isset($items[$delta]->lat)
      && isset($items[$delta]->lng)
    ) {
      $default_field_values = [
        'lat' => $items[$delta]->lat,
        'lng' => $items[$delta]->lng,
      ];
    }

    $element = [
      '#type' => 'geolocation_input',
      '#title' => $element['#title'] ?? '',
      '#title_display' => $element['#title_display'] ?? '',
      '#description' => $element['#description'] ?? '',
      '#attributes' => [
        'class' => [
          'geolocation-widget-input',
        ],
      ],
    ];

    if ($default_field_values) {
      $element['#default_value'] = [
        'lat' => $default_field_values['lat'],
        'lng' => $default_field_values['lng'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL): array {
    $element = parent::form($items, $form, $form_state, $get_delta);

    $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'], [
      'drupalSettings' => [
        'geolocation' => [
          'widgetSettings' => [
            $element['#attributes']['id'] => [
              'widgetSubscribers' => [
                'geolocation_field' => [
                  'import_path' => base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/WidgetSubscriber/GeolocationFieldWidget.js',
                  'settings' => [
                    'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
                    'field_name' => $this->fieldDefinition->getName(),
                    'field_type' => $this->fieldDefinition->getType(),
                  ],
                ],
                'geolocation_map' => [
                  'import_path' => base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/WidgetSubscriber/GeolocationFieldMapWidget.js',
                  'settings' => [
                    'mapId' => $element['map']['#id'],
                    'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
                    'field_name' => $this->fieldDefinition->getName(),
                    'field_type' => $this->fieldDefinition->getType(),
                    'feature_id' => $this->getWidgetFeatureId(),
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    foreach ($items as $index => $item) {
      assert($item instanceof GeolocationItem);
      if ($item->isEmpty()) {
        continue;
      }
      $element['map']['locations']['location-' . $index] = [
        '#type' => 'geolocation_map_location',
        '#title' => ($index + 1) . ': ' . $item->getValue()['lat'] . ", " . $item->getValue()['lng'],
        '#label' => ($index + 1),
        '#coordinates' => [
          'lat' => $item->getValue()['lat'],
          'lng' => $item->getValue()['lng'],
        ],
        '#draggable' => TRUE,
        '#attributes' => [
          'data-geolocation-widget-index' => $index,
        ],
      ];
    }

    return $element;
  }

}
