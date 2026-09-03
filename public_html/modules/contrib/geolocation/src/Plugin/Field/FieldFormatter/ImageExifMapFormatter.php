<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;

/**
 * Plugin implementation of the 'geolocation' formatter.
 */
#[FieldFormatter(
  id: 'image_exif_map',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Formatter - Map by Image EXIF data'),
  field_types: ['image']
)]
class ImageExifMapFormatter extends GeolocationMapFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $dataProviderId = 'image_field_provider';

}
