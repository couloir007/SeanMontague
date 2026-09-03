<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'geolocation_geometry_geojson' widget.
 */
#[FieldWidget(
  id: 'geolocation_geometry_geojson',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry GeoJSON'),
  field_types: [
    'geolocation_geometry_geometry',
    'geolocation_geometry_geometrycollection',
    'geolocation_geometry_point',
    'geolocation_geometry_linestring',
    'geolocation_geometry_polygon',
    'geolocation_geometry_multipoint',
    'geolocation_geometry_multilinestring',
    'geolocation_geometry_multipolygon',
  ]
)]
class GeolocationGeometryGeoJsonWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $description_link = Link::fromTextAndUrl($this->t('GeoJSON data'), Url::fromUri('//en.wikipedia.org/wiki/GeoJSON', ['attributes' => ['target' => '_blank']]))->toString();

    $element['geojson'] = [
      '#type' => 'textarea',
      '#title' => $element['#title'],
      '#default_value' => $items[$delta]->geojson ?? NULL,
      '#empty_value' => '',
      '#description' => $this->t('Please enter valid %wikipedia.', ['%wikipedia' => $description_link]),
      '#required' => $element['#required'],
    ];

    return $element;
  }

}
