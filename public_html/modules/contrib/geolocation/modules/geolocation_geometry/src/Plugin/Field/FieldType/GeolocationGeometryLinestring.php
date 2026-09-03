<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'geolocation' field type.
 */
#[FieldType(
  id: 'geolocation_geometry_linestring',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry - Line'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup("This field stores spatial geometry of type 'LineString'."),
  category: 'geo_spatial',
  default_widget: 'geolocation_geometry_geojson',
  default_formatter: 'geolocation_geometry_data'
)]
class GeolocationGeometryLinestring extends GeolocationGeometryBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['geometry']['pgsql_type'] = "geometry('LINESTRING')";
    $schema['columns']['geometry']['mysql_type'] = 'linestring';

    return $schema;
  }

}
