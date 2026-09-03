<?php

namespace Drupal\geolocation;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * {@inheritdoc}
 *
 * @extends FieldItemListInterface<\Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem>
 *
 * @property ?float $lat
 *    Latitude.
 * @property ?float $lng
 *     Longitude.
 * @property ?float $lat_sin
 *     Latitude Sine.
 * @property ?float $lat_cos
 *     Latitude Cosine.
 * @property ?float $lng_rad
 *     Longitude Radian.
 * @property ?mixed $data
 *     Data.
 */
interface GeolocationItemListInterface extends FieldItemListInterface {}
