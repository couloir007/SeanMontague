<?php

namespace Drupal\geolocation_address\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\geolocation\Plugin\Field\FieldFormatter\GeolocationMapFormatterBase;

/**
 * Plugin implementation of the 'geolocation' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_address',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Address Formatter - Map'),
  field_types: ['address']
)]
class GeolocationAddressFormatter extends GeolocationMapFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $dataProviderId = 'geolocation_address_field_provider';

}
