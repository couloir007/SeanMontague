<?php

namespace Drupal\geolocation_geofield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\geolocation\Plugin\Field\FieldFormatter\GeolocationMapFormatterBase;

/**
 * Plugin implementation of the 'geofield' formatter.
 */
#[FieldFormatter(
  id: 'geolocation_geofield',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Geofield Formatter - Map'),
  field_types: ['geofield']
)]
class GeolocationGeofieldMapFormatter extends GeolocationMapFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $dataProviderId = 'geofield';

}
