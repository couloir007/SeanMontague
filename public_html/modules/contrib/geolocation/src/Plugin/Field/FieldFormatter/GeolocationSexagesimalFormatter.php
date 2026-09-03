<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem;

/**
 * Plugin implementation of the 'geolocation_sexagesimal' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_sexagesimal',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Sexagesimal / GPS / DMS'),
  field_types: ['geolocation']
)]
class GeolocationSexagesimalFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    foreach ($items as $delta => $item) {
      assert($item instanceof GeolocationItem);
      $element[$delta] = [
        '#theme' => 'geolocation_sexagesimal_formatter',
        '#lat' => $item::decimalToSexagesimal($item->lat),
        '#lng' => $item::decimalToSexagesimal($item->lng),
      ];
    }

    return $element;
  }

}
