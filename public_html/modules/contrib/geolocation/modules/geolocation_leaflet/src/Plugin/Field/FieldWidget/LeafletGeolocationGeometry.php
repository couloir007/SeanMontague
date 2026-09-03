<?php

namespace Drupal\geolocation_leaflet\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\geolocation_geometry\Plugin\Field\FieldWidget\GeolocationGeometryMapWidget;

/**
 * Plugin implementation of 'geolocation_geometry_widget_leaflet' widget.
 */
#[FieldWidget(
  id: 'geolocation_geometry_widget_leaflet',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry Leaflet - GeoJSON'),
  field_types: [
    'geolocation_geometry_point',
    'geolocation_geometry_multi_point',
    'geolocation_geometry_linestring',
    'geolocation_geometry_polygon',
  ]
)]
class LeafletGeolocationGeometry extends GeolocationGeometryMapWidget {

  /**
   * {@inheritdoc}
   */
  protected ?string $mapProviderId = 'leaflet';

  /**
   * {@inheritdoc}
   */
  public function getWidgetFeatureId(): string {
    return 'leaflet_geometry_widget_map_connector';
  }

}
