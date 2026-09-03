<?php

namespace Drupal\geolocation\Plugin\geolocation\LocationInput;

use Drupal\geolocation\Attribute\LocationInput;
use Drupal\geolocation\LocationInputBase;
use Drupal\geolocation\LocationInputInterface;

/**
 * Location based proximity center.
 */
#[LocationInput(
  id: 'coordinates',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Coordinates input'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Simple latitude, longitude input.')
)]
class Coordinates extends LocationInputBase implements LocationInputInterface {}
