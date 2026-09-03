<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\Plugin\Field\FieldWidget\GeolocationMapWidgetBase;
use Drupal\geolocation_geometry\Plugin\Field\FieldType\GeolocationGeometryBase;
use Drupal\geolocation_geometry\Plugin\geolocation\DataProvider\GeolocationGeometry;

/**
 * Plugin implementation of the 'geolocation_map' widget.
 */
abstract class GeolocationGeometryMapWidget extends GeolocationMapWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['#type'] = 'container';
    $element['#attributes'] = [
      'data-geometry-type' => str_replace('geolocation_geometry_', '', $this->fieldDefinition->getType()),
      'class' => [
        str_replace('_', '-', $this->getPluginId()) . '-geojson',
      ],
    ];

    $element['geojson'] = [
      '#type' => 'textarea',
      '#title' => $this->t('GeoJSON'),
      '#default_value' => $items[$delta]->geojson ?? NULL,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#attributes' => [
        'class' => [
          'geolocation-geometry-widget-geojson-input',
        ],
      ],
    ];

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
                'geolocation_geometry_field' => [
                  'import_path' => base_path() . $this->moduleHandler->getModule('geolocation_geometry')->getPath() . '/js/WidgetSubscriber/GeolocationGeometryFieldWidget.js',
                  'settings' => [
                    'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
                    'field_name' => $this->fieldDefinition->getName(),
                    'field_type' => $this->fieldDefinition->getType(),
                  ],
                ],
                'geolocation_geometry_map' => [
                  'import_path' => base_path() . $this->moduleHandler->getModule('geolocation_geometry')->getPath() . '/js/WidgetSubscriber/GeolocationGeometryFieldMapWidget.js',
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
      assert($item instanceof GeolocationGeometryBase);
      if ($item->isEmpty()) {
        continue;
      }

      if (!json_validate($item->get('geojson')->getValue())) {
        continue;
      }

      $element['map']['locations']['location-' . $index] = GeolocationGeometry::getRenderedElementByGeoJSON(json_decode($item->get('geojson')->getValue()));
      $element['map']['locations']['location-' . $index]['#title'] = ($index + 1);
      $element['map']['locations']['location-' . $index]['#label'] = ($index + 1);
      $element['map']['locations']['location-' . $index]['#attributes'] = [
        'data-geolocation-widget-index' => $index,
      ];
    }

    return $element;
  }

}
