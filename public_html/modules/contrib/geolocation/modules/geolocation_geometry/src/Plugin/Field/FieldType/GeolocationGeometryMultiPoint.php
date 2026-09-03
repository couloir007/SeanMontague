<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'geolocation' field type.
 */
#[FieldType(
  id: 'geolocation_geometry_multipoint',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry - MultiPoint'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup("This field stores spatial geometry of type 'MultiPoint'."),
  category: 'geo_spatial',
  default_widget: 'geolocation_geometry_geojson',
  default_formatter: 'geolocation_geometry_data'
)]
class GeolocationGeometryMultiPoint extends GeolocationGeometryBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['geometry']['pgsql_type'] = "geometry('MULTIPOINT')";
    $schema['columns']['geometry']['mysql_type'] = 'multipoint';

    return $schema;
  }

}
