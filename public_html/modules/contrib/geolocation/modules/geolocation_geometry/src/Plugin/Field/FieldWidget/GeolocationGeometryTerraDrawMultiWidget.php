<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation_geometry\Plugin\geolocation\DataProvider\GeolocationGeometry;

/**
 * Plugin implementation of the 'geolocation_geometry_terra_draw_multi' widget.
 *
 * A multi geometry is one field item made of several parts, so unlike the
 * single geometry widget this gives every item a map of its own, on which
 * everything drawn together makes up that item's geometry.
 *
 * The field types must not overlap with those of the parent widget - both
 * share a label, which is only unambiguous while a field type is offered
 * exactly one of them.
 */
#[FieldWidget(
  id: 'geolocation_geometry_terra_draw_multi',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry Terra Draw'),
  field_types: [
    'geolocation_geometry_multipoint',
    'geolocation_geometry_multilinestring',
    'geolocation_geometry_multipolygon',
  ]
)]
class GeolocationGeometryTerraDrawMultiWidget extends GeolocationGeometryTerraDrawWidget {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetFeatureId(): string {
    return 'geolocation_geometry_terra_draw_multi_connector';
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();

    // Each item is its own widget: one map, one input, one broker between
    // them. Registering that here rather than on the field puts it inside the
    // subtree "Add another item" returns, so a new item arrives complete.
    $item_id = Html::getUniqueId('edit_' . $this->fieldDefinition->getName() . '_' . $delta . '_widget');

    $element['#type'] = 'container';
    $element['#attributes'] = [
      'id' => $item_id,
      'data-widget-type' => $this->getPluginId(),
      'data-geometry-type' => str_replace('geolocation_geometry_', '', $this->fieldDefinition->getType()),
      'class' => [
        'geolocation-map-widget',
        str_replace('_', '-', $this->getPluginId()) . '-geojson',
      ],
    ];

    $element['map'] = [
      '#type' => 'geolocation_map',
      '#weight' => -10,
      '#settings' => $settings['map_provider_settings'],
      '#id' => Html::getUniqueId('edit_' . $this->fieldDefinition->getName() . '_' . $delta . '_map'),
      '#maptype' => $this->mapProviderId ?? $settings['map_provider_id'] ?? NULL,
      '#context' => ['widget' => $this],
      'locations' => [],
    ];

    $element['map']['#settings']['map_features'] = array_merge(
      $element['map']['#settings']['map_features'] ?? [],
      [
        $this->getWidgetFeatureId() => [
          'enabled' => TRUE,
          'settings' => [
            'field_name' => $this->fieldDefinition->getName(),
            'field_type' => $this->fieldDefinition->getType(),
          ],
        ],
      ]
    );

    $geojson = $items[$delta]->geojson ?? NULL;

    // Rebuilding the widget over AJAX - adding an item - leaves the field item
    // holding the stored value, while the input holds what has been drawn on
    // the map since. Without this the rebuilt map would come back empty.
    $submitted = NestedArray::getValue(
      $form_state->getUserInput(),
      array_merge($element['#field_parents'] ?? [], [$this->fieldDefinition->getName(), $delta, 'geojson'])
    );
    if (is_string($submitted)) {
      $geojson = $submitted;
    }

    if ($geojson && json_validate($geojson)) {
      $element['map']['locations']['location'] = GeolocationGeometry::getRenderedElementByGeoJSON(json_decode($geojson));
    }

    $element['map'] = $this->mapCenterManager->alterMap($element['map'], $settings['centre']);

    $element['geojson'] = [
      '#type' => 'textarea',
      '#title' => $this->t('GeoJSON'),
      '#default_value' => $geojson,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#attributes' => [
        'class' => [
          'geolocation-geometry-widget-geojson-input',
        ],
      ],
    ];

    $module_path = base_path() . $this->moduleHandler->getModule('geolocation_geometry')->getPath();
    $subscriber_settings = [
      'field_name' => $this->fieldDefinition->getName(),
      'field_type' => $this->fieldDefinition->getType(),
    ];

    $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'] ?? [], [
      'library' => [
        'geolocation/geolocation.widget.map',
      ],
      'drupalSettings' => [
        'geolocation' => [
          'widgetSettings' => [
            $item_id => [
              'brokerImportPath' => base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/GeolocationWidgetBroker.js',
              'widgetSubscribers' => [
                'geolocation_geometry_item' => [
                  'import_path' => $module_path . '/js/WidgetSubscriber/GeolocationGeometryItemFieldWidget.js',
                  'settings' => $subscriber_settings,
                ],
                'geolocation_geometry_item_map' => [
                  'import_path' => $module_path . '/js/WidgetSubscriber/GeolocationGeometryFieldMapMultiWidget.js',
                  'settings' => $subscriber_settings + [
                    'mapId' => $element['map']['#id'],
                    'feature_id' => $this->getWidgetFeatureId(),
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL): array {
    $element = parent::form($items, $form, $form_state, $get_delta);

    // Every item drew its own map and registered its own broker, so neither
    // the field level map nor the field level widget the parent widgets add
    // has anything left to do.
    unset($element['map'], $element['map_provider_settings']);

    $field_id = $element['#attributes']['id'] ?? NULL;
    $element['#attributes']['class'] = array_values(array_diff($element['#attributes']['class'] ?? [], ['geolocation-map-widget']));
    if ($field_id) {
      unset($element['#attached']['drupalSettings']['geolocation']['widgetSettings'][$field_id]);
    }

    $element['map_description']['#description'] = $this->t('Everything drawn on a map makes up the geometry of that item. Use <em>Draw</em> to add a part - a point is placed with a single click, a line or polygon is finished with a double click. Use <em>Edit</em> to select a part, then move it or its points, or remove it with <em>Delete</em>.');

    return $element;
  }

}
