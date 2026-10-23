<?php

namespace Drupal\geolocation_search_api\Plugin\geolocation\DataProvider;

use Drupal\geolocation\Attribute\DataProvider;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
use Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem;
use Drupal\geolocation_geometry\Plugin\Field\FieldType\GeolocationGeometryPoint;
use Drupal\geolocation_geometry\Plugin\geolocation\DataProvider\GeolocationGeometry;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Plugin\views\field\SearchApiEntityField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides Google Maps.
 */
#[DataProvider(
  id: 'search_api',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Search API'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Search API indexed fields support, works with Search API Location module too.')
)]
class SearchAPI extends DataProviderBase implements DataProviderInterface {

  /**
   * Get Search API data type.
   *
   * @param \Drupal\search_api\Plugin\views\field\SearchApiEntityField $viewsField
   *   Views Field.
   *
   * @return string|false
   *   Type or false.
   */
  protected function getSearchApiDataType(SearchApiEntityField $viewsField): string|false {
    $index_id = str_replace('search_api_index_', '', $viewsField->table);
    $index = Index::load($index_id);
    if (empty($index)) {
      return FALSE;
    }

    /** @var \Drupal\search_api\Item\FieldInterface|null $search_api_field */
    $search_api_field = $index->getField($viewsField->field);
    if (empty($search_api_field)) {
      return FALSE;
    }
    return $search_api_field->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function isViewsGeoOption(FieldPluginBase $viewsField): bool {
    if (!$viewsField instanceof SearchApiEntityField) {
      return FALSE;
    }

    if (in_array($this->getSearchApiDataType($viewsField), [
      'location',
      'geolocation_coordinates',
      'geolocation_geometry',
    ])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getShapesFromViewsRow(ResultRow $row, ?FieldPluginBase $viewsField = NULL): array {
    $shapes = [];

    if (!($viewsField instanceof SearchApiEntityField)) {
      return [];
    }

    if ($this->getSearchApiDataType($viewsField) !== 'geolocation_geometry') {
      return [];
    }

    foreach ($viewsField->getItems($row) as $item) {
      if (!empty($item['value'])) {
        $geojson = json_decode($item['value']);
        if (!is_object($geojson)) {
          continue;
        }

        $shapes[] = GeolocationGeometry::getRenderedElementByGeoJSON($geojson);
      }
      elseif (!empty($item['raw'])) {
        $geojson = json_decode($item['raw']->get('geojson')->getValue());
        if (!is_object($geojson)) {
          continue;
        }

        $shapes[] = GeolocationGeometry::getRenderedElementByGeoJSON($geojson);
      }
    }

    return $shapes;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsFromViewsRow(ResultRow $row, ?FieldPluginBase $viewsField = NULL): array {
    $locations = [];

    if (!($viewsField instanceof SearchApiEntityField)) {
      return [];
    }

    if (!in_array($this->getSearchApiDataType($viewsField), ['location', 'geolocation_coordinates'])) {
      return [];
    }

    foreach ($viewsField->getItems($row) as $item) {
      $coordinates = NULL;
      $raw = $item['raw'] ?? NULL;

      // A geometry field yields GeoJSON here, a geolocation field "lat,lng".
      if (!empty($item['value'])) {
        $value = (string) $item['value'];
        $geojson = json_decode($value);

        if (
          is_object($geojson)
          && !empty($geojson->coordinates)
        ) {
          $coordinates = [
            'lat' => $geojson->coordinates[1],
            'lng' => $geojson->coordinates[0],
          ];
        }
        else {
          $pieces = array_map('trim', explode(',', $value));
          if (
            count($pieces) === 2
            && is_numeric($pieces[0])
            && is_numeric($pieces[1])
          ) {
            $coordinates = [
              'lat' => $pieces[0],
              'lng' => $pieces[1],
            ];
          }
        }
      }
      // Which raw item arrives depends on the field type indexed, not on the
      // data type it was indexed as.
      elseif ($raw instanceof GeolocationItem) {
        $coordinates = [
          'lat' => $raw->get('lat')->getValue(),
          'lng' => $raw->get('lng')->getValue(),
        ];
      }
      elseif ($raw instanceof GeolocationGeometryPoint) {
        $geojson = json_decode($raw->get('geojson')->getValue());
        if (
          is_object($geojson)
          && !empty($geojson->coordinates)
        ) {
          $coordinates = [
            'lat' => $geojson->coordinates[1],
            'lng' => $geojson->coordinates[0],
          ];
        }
      }

      if (!$coordinates) {
        continue;
      }

      $locations[] = [
        '#type' => 'geolocation_map_location',
        '#coordinates' => $coordinates,
      ];
    }

    return $locations;
  }

}
