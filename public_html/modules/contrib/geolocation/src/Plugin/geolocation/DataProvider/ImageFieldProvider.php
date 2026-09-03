<?php

namespace Drupal\geolocation\Plugin\geolocation\DataProvider;

use Drupal\geolocation\Attribute\DataProvider;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Provides image field data integration.
 */
#[DataProvider(id: 'image_field_provider',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Image Field'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('EXIF data from images.'))]
class ImageFieldProvider extends DataProviderBase implements DataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function isViewsGeoOption(FieldPluginBase $viewsField): bool {
    $entity_type = $viewsField->getEntityType();
    $field_name = $viewsField->field;
    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
    if (!isset($fields[$field_name])) {
      return FALSE;
    }
    $field_storage = $fields[$field_name];

    return $field_storage->getType() === "image";
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool {
    return ($fieldDefinition->getType() == 'image');
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsFromItem(FieldItemInterface $fieldItem): array {
    if ($fieldItem instanceof ImageItem) {
      $exif = exif_read_data($fieldItem->entity?->getFileUri() ?? NULL);
      if (!$exif) {
        return [];
      }

      $lat = $exif["GPSLatitude"] ?? FALSE;
      $lon = $exif["GPSLongitude"] ?? FALSE;
      $lat_ref = $exif["GPSLatitudeRef"] ?? FALSE;
      $lon_ref = $exif["GPSLongitudeRef"] ?? FALSE;

      if (!$lat || !$lon || !$lat_ref || !$lon_ref) {
        return [];
      }

      return [
        [
          '#type' => 'geolocation_map_location',
          '#coordinates' => [
            'lat' => self::exifToCoordinates($lat, $lat_ref),
            'lng' => self::exifToCoordinates($lon, $lon_ref),
          ],
        ],
      ];
    }

    return [];
  }

  /**
   * Format EXIF data.
   *
   * @param array|string $coordinate
   *   EXIF formatted coordinates.
   * @param string $hemisphere
   *   EXIF formatted Hemisphere.
   *
   * @return float|int
   *   Coordinate.
   */
  protected static function exifToCoordinates(array|string $coordinate, string $hemisphere): float|int {
    if (is_string($coordinate)) {
      $coordinate = array_map("trim", explode(",", $coordinate));
    }

    for ($i = 0; $i < 3; $i++) {
      $part = explode('/', $coordinate[$i]);
      if (count($part) == 1) {
        $coordinate[$i] = $part[0];
      }
      elseif (count($part) == 2) {
        $coordinate[$i] = floatval($part[0]) / floatval($part[1]);
      }
      else {
        $coordinate[$i] = 0;
      }
    }
    [$degrees, $minutes, $seconds] = $coordinate;
    $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
    return $sign * ($degrees + $minutes / 60 + $seconds / 3600);
  }

}
