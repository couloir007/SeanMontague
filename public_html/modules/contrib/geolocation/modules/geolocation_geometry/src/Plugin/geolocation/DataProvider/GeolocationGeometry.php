<?php

namespace Drupal\geolocation_geometry\Plugin\geolocation\DataProvider;

use Drupal\geolocation\Attribute\DataProvider;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides GPX.
 */
#[DataProvider(
  id: 'geolocation_geometry',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geometry'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Points, Polygons, Polyines.')
)]
class GeolocationGeometry extends DataProviderBase implements DataProviderInterface {

  /**
   * {@inheritdoc}
   */
  protected static function defaultSettings(): array {
    $settings = parent::defaultSettings();

    $settings['color_randomize'] = TRUE;

    $settings['stroke_color'] = '#FF0044';
    $settings['stroke_width'] = 1;
    $settings['stroke_opacity'] = 0.9;

    $settings['fill_color'] = '#0033FF';
    $settings['fill_opacity'] = 0.2;

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

        if (in_array($field_storage_definition->getType(), [
          'geolocation_geometry_geometry',
          'geolocation_geometry_geometrycollection',
          'geolocation_geometry_point',
          'geolocation_geometry_linestring',
          'geolocation_geometry_polygon',
          'geolocation_geometry_multipoint',
          'geolocation_geometry_multilinestring',
          'geolocation_geometry_multipolygon',
        ])) {
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

    $element['color_randomize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Randomize colors'),
      '#description' => $this->t('Set stroke and fill color to the same random value. Enabling ignores any set specific color values.'),
      '#default_value' => $settings['color_randomize'],
    ];

    $element['stroke_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Stroke color'),
      '#default_value' => $settings['stroke_color'],
    ];

    $element['stroke_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Stroke Width'),
      '#description' => $this->t('Width of the stroke in pixels.'),
      '#default_value' => $settings['stroke_width'],
    ];

    $element['stroke_opacity'] = [
      '#type' => 'number',
      '#step' => 0.01,
      '#title' => $this->t('Stroke Opacity'),
      '#description' => $this->t('Opacity of the stroke from 1 = fully visible, 0 = complete see through.'),
      '#default_value' => $settings['stroke_opacity'],
    ];

    $element['fill_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Fill color'),
      '#default_value' => $settings['fill_color'],
    ];

    $element['fill_opacity'] = [
      '#type' => 'number',
      '#step' => 0.01,
      '#title' => $this->t('Fill Opacity'),
      '#description' => $this->t('Opacity of the polygons from 1 = fully visible, 0 = complete see through.'),
      '#default_value' => $settings['fill_opacity'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsFromViewsRow(ResultRow $row, ?FieldPluginBase $viewsField = NULL): array {
    $locations = parent::getLocationsFromViewsRow($row, $viewsField);

    $current_style = $viewsField->displayHandler->getPlugin('style');

    if (!is_subclass_of($current_style, 'Drupal\geolocation\Plugin\views\style\GeolocationStyleBase')) {
      return $locations;
    }

    foreach ($locations as &$location) {
      if (!is_array($location)) {
        continue;
      }
      $location['#title'] = $current_style->getTitleField($row);
      $location['#label'] = $current_style->getLabelField($row);
    }

    return $locations;
  }

  /**
   * {@inheritdoc}
   */
  public function getShapesFromViewsRow(ResultRow $row, ?FieldPluginBase $viewsField = NULL): array {
    $shapes = parent::getShapesFromViewsRow($row, $viewsField);

    if (empty($shapes)) {
      return $shapes;
    }

    $current_style = $viewsField->displayHandler->getPlugin('style');

    if (!is_subclass_of($current_style, 'Drupal\geolocation\Plugin\views\style\GeolocationStyleBase')) {
      return $shapes;
    }

    foreach ($shapes as &$shape) {
      if (!is_array($shape)) {
        continue;
      }
      $shape['#title'] = $current_style->getTitleField($row);
    }

    return $shapes;
  }

  /**
   * {@inheritdoc}
   */
  public function getShapesFromItem(FieldItemInterface $fieldItem): array {
    $settings = $this->getSettings();

    $shapes = [];

    foreach ($this->getShapeGeometriesFromGeoJson($fieldItem->get('geojson')->getString()) as $geometry) {
      $shapes[] = self::getRenderedElementByGeoJSON($geometry, $settings);
    }

    return $shapes;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsFromItem(FieldItemInterface $fieldItem): array {
    $settings = $this->getSettings();

    $locations = [];

    foreach ($this->getLocationGeometriesFromGeoJson($fieldItem->get('geojson')->getString()) as $geometry) {
      $locations[] = self::getRenderedElementByGeoJSON($geometry, $settings);
    }

    return $locations;
  }

  /**
   * Get render element.
   *
   * @param object $geojson
   *   GeoJSON.
   * @param array|null $settings
   *   Optional settings.
   *
   * @return array
   *   Render Element.
   */
  public static function getRenderedElementByGeoJSON(object $geojson, ?array $settings = NULL): array {
    $settings = $settings ?? self::defaultSettings();

    if (!isset($settings['stroke_width'])) {
      $settings['stroke_width'] = self::defaultSettings()['stroke_width'];
    }
    if (!isset($settings['stroke_opacity'])) {
      $settings['stroke_opacity'] = self::defaultSettings()['stroke_opacity'];
    }
    if (!isset($settings['fill_opacity'])) {
      $settings['fill_opacity'] = self::defaultSettings()['fill_opacity'];
    }

    $random_color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    switch ($geojson->type) {
      case 'Point':
        return [
          '#type' => 'geolocation_map_location',
          '#coordinates' => [
            'lat' => $geojson->coordinates[1],
            'lng' => $geojson->coordinates[0],
          ],
        ];

      case 'MultiPoint':
        $container = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'geolocation-multipoint',
            ],
          ],
        ];
        foreach ($geojson->coordinates as $key => $point) {
          $position = [
            '#type' => 'geolocation_map_location',
            '#coordinates' => [
              'lat' => $point->coordinates[1],
              'lng' => $point->coordinates[0],
            ],
          ];
          $container[$key] = $position;
        }
        return $container;

      case 'Polygon':
        return [
          '#type' => 'geolocation_map_shape',
          '#geometry' => json_encode($geojson),
          '#geometry_type' => 'polygon',
          '#stroke_color' => $settings['color_randomize'] ? $random_color : $settings['stroke_color'],
          '#stroke_width' => (int) $settings['stroke_width'],
          '#stroke_opacity' => (float) $settings['stroke_opacity'],
          '#fill_color' => $settings['color_randomize'] ? $random_color : $settings['fill_color'],
          '#fill_opacity' => (float) $settings['fill_opacity'],
        ];

      case 'MultiPolygon':
        return [
          '#type' => 'geolocation_map_shape',
          '#geometry' => json_encode($geojson),
          '#geometry_type' => 'multipolygon',
          '#stroke_color' => $settings['color_randomize'] ? $random_color : $settings['stroke_color'],
          '#stroke_width' => (int) $settings['stroke_width'],
          '#stroke_opacity' => (float) $settings['stroke_opacity'],
          '#fill_color' => $settings['color_randomize'] ? $random_color : $settings['fill_color'],
          '#fill_opacity' => (float) $settings['fill_opacity'],
        ];

      case 'LineString':
        return [
          '#type' => 'geolocation_map_shape',
          '#geometry' => json_encode($geojson),
          '#geometry_type' => 'line',
          '#stroke_color' => $settings['color_randomize'] ? $random_color : $settings['stroke_color'],
          '#stroke_width' => (int) $settings['stroke_width'],
          '#stroke_opacity' => (float) $settings['stroke_opacity'],
        ];

      case 'MultiLineString':
        return [
          '#type' => 'geolocation_map_shape',
          '#geometry' => json_encode($geojson),
          '#geometry_type' => 'multiline',
          '#stroke_color' => $settings['color_randomize'] ? $random_color : $settings['stroke_color'],
          '#stroke_width' => (int) $settings['stroke_width'],
          '#stroke_opacity' => (float) $settings['stroke_opacity'],
        ];

      default:
        return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool {
    return (in_array($fieldDefinition->getType(), [
      'geolocation_geometry_geometry',
      'geolocation_geometry_geometrycollection',
      'geolocation_geometry_point',
      'geolocation_geometry_linestring',
      'geolocation_geometry_polygon',
      'geolocation_geometry_multipoint',
      'geolocation_geometry_multilinestring',
      'geolocation_geometry_multipolygon',
    ]));
  }

  /**
   * Get Shapes from GeoJSON.
   *
   * @param string $geoJson
   *   GeoJSON.
   *
   * @return array
   *   Shapes.
   */
  protected function getShapeGeometriesFromGeoJson(string $geoJson): array {
    $geometries = [];

    $json = json_decode($geoJson);

    if (
      is_object($json)
      && isset($json->type)
    ) {
      $json = [$json];
    }

    foreach ($json as $entry) {
      if (empty($entry->type)) {
        continue;
      }
      switch ($entry->type) {
        case 'FeatureCollection':
          if (empty($entry->features)) {
            continue 2;
          }
          $geometries = array_merge($geometries, $this->getShapeGeometriesFromGeoJson(is_string($entry->features) ?: json_encode($entry->features)));
          break;

        case 'Feature':
          if (empty($entry->geometry)) {
            continue 2;
          }
          $geometries = array_merge($geometries, $this->getShapeGeometriesFromGeoJson(is_string($entry->geometry) ?: json_encode($entry->geometry)));
          break;

        case 'GeometryCollection':
          if (empty($entry->geometries)) {
            continue 2;
          }
          $geometries = array_merge($geometries, $this->getShapeGeometriesFromGeoJson(is_string($entry->geometries) ?: json_encode($entry->geometries)));
          break;

        case 'MultiPolygon':
        case 'Polygon':
        case 'MultiLineString':
        case 'LineString':
          if (empty($entry->coordinates)) {
            continue 2;
          }
          $geometries[] = $entry;
          break;
      }
    }

    return $geometries;
  }

  /**
   * Get Locations from GeoJSON.
   *
   * @param string $geoJson
   *   GeoJSON.
   *
   * @return array
   *   Locations.
   */
  protected function getLocationGeometriesFromGeoJson(string $geoJson): array {
    $geometries = [];

    $json = json_decode($geoJson);

    if (
      is_object($json)
      && isset($json->type)
    ) {
      $json = [$json];
    }

    foreach ($json as $entry) {
      if (empty($entry->type)) {
        continue;
      }
      switch ($entry->type) {
        case 'FeatureCollection':
          if (empty($entry->features)) {
            continue 2;
          }
          $geometries = array_merge($geometries, $this->getLocationGeometriesFromGeoJson(is_string($entry->features) ?: json_encode($entry->features)));
          break;

        case 'Feature':
          if (empty($entry->geometry)) {
            continue 2;
          }
          $geometries = array_merge($geometries, $this->getLocationGeometriesFromGeoJson(is_string($entry->geometry) ?: json_encode($entry->geometry)));
          break;

        case 'GeometryCollection':
          if (empty($entry->geometries)) {
            continue 2;
          }
          $geometries = array_merge($geometries, $this->getLocationGeometriesFromGeoJson(is_string($entry->geometries) ?: json_encode($entry->geometries)));
          break;

        case 'MultiPoint':
        case 'Point':
          if (empty($entry->coordinates)) {
            continue 2;
          }
          $geometries[] = $entry;
          break;
      }
    }

    return $geometries;
  }

}
