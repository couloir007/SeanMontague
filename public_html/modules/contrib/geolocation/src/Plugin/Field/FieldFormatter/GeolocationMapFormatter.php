<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;

/**
 * Plugin implementation of the 'geolocation' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_map',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Formatter - Map'),
  field_types: ['geolocation']
)]
class GeolocationMapFormatter extends GeolocationMapFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $dataProviderId = 'geolocation_field_provider';

}
