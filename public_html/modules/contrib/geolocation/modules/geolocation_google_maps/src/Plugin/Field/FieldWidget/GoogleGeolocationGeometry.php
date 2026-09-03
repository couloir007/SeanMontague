<?php

namespace Drupal\geolocation_google_maps\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\geolocation_geometry\Plugin\Field\FieldWidget\GeolocationGeometryMapWidget;

/**
 * Plugin implementation of 'geolocation_geometry_widget_google' widget.
 */
#[FieldWidget(
  id: 'geolocation_geometry_widget_google',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry Google - GeoJSON'),
  field_types: [
    'geolocation_geometry_point',
    'geolocation_geometry_multi_point',
    'geolocation_geometry_linestring',
    'geolocation_geometry_polygon',
  ]
)]
class GoogleGeolocationGeometry extends GeolocationGeometryMapWidget {

  /**
   * {@inheritdoc}
   */
  protected ?string $mapProviderId = 'google_maps';

  /**
   * {@inheritdoc}
   */
  public function getWidgetFeatureId(): string {
    return 'google_maps_geometry_widget_map_connector';
  }

}
