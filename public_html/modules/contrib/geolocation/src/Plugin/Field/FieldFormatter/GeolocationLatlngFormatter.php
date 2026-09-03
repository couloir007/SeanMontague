<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem;

/**
 * Plugin implementation of the 'geolocation_latlng' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_latlng',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Lat/Lng'),
  field_types: ['geolocation']
)]
class GeolocationLatlngFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    foreach ($items as $delta => $item) {
      assert($item instanceof GeolocationItem);
      $element[$delta] = [
        '#theme' => 'geolocation_latlng_formatter',
        '#lat' => $item->lat,
        '#lng' => $item->lng,
      ];
    }

    return $element;
  }

}
