<?php

namespace Drupal\geolocation_search_api\Plugin\geolocation\DataProvider;

use Drupal\geolocation\Attribute\DataProvider;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
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

    $search_api_data_type = $this->getSearchApiDataType($viewsField);
    if (!in_array($search_api_data_type, ['location', 'geolocation_coordinates'])) {
      return [];
    }

    foreach ($viewsField->getItems($row) as $item) {
      if (!empty($item['value'])) {
        switch ($search_api_data_type) {
          case 'geolocation_coordinates':
            $geojson = json_decode($item['value']);
            if (
              !is_object($geojson)
              || empty($geojson->coordinates)
            ) {
              continue 2;
            }
            $locations[] = [
              '#type' => 'geolocation_map_location',
              '#coordinates' => [
                'lat' => $geojson->coordinates[1],
                'lng' => $geojson->coordinates[0],
              ],
            ];
            break;

          case 'location':
            $pieces = explode(',', $item['value']);
            if (count($pieces) != 2) {
              continue 2;
            }

            $locations[] = [
              '#type' => 'geolocation_map_location',
              '#coordinates' => [
                'lat' => $pieces[0],
                'lng' => $pieces[1],
              ],
            ];
            break;

          default:
            continue 2;
        }

      }
      elseif (!empty($item['raw'])) {

        switch ($search_api_data_type) {
          case 'geolocation_coordinates':
            if (!is_a($item['raw'], GeolocationGeometryPoint::class)) {
              continue 2;
            }

            $geojson = json_decode($item['raw']->get('geojson')->getValue());
            if (
              !is_object($geojson)
              || empty($geojson->coordinates)
            ) {
              continue 2;
            }
            $locations[] = [
              '#type' => 'geolocation_map_location',
              '#coordinates' => [
                'lat' => $geojson->coordinates[1],
                'lng' => $geojson->coordinates[0],
              ],
            ];
            break;

          case 'location':
            /** @var \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem $geolocation_item */
            $geolocation_item = $item['raw'];
            $locations[] = [
              '#type' => 'geolocation_map_location',
              '#coordinates' => [
                'lat' => $geolocation_item->get('lat')->getValue(),
                'lng' => $geolocation_item->get('lng')->getValue(),
              ],
            ];
            break;
        }
      }
    }

    return $locations;
  }

}
