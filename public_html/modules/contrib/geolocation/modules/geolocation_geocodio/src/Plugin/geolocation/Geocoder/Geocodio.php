<?php

namespace Drupal\geolocation_geocodio\Plugin\geolocation\Geocoder;

use Drupal\geolocation\Attribute\Geocoder;
use Drupal\Core\Utility\Error;
use Drupal\geolocation\GeocoderBase;
use Drupal\geolocation\GeocoderInterface;
use Drupal\geolocation\KeyProvider;
use Geocodio\Geocodio as GeocodioApi;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a Geocodio integration.
 */
#[Geocoder(
  id: 'geocodio',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geocodio'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.geocod.io/docs/ for details.'),
  locationCapable: TRUE,
  boundaryCapable: FALSE,
  frontendCapable: FALSE
)]
class Geocodio extends GeocoderBase implements GeocoderInterface {

  /**
   * {@inheritdoc}
   */
  public function geocode(string $address): ?array {

    if (empty($address)) {
      return NULL;
    }

    // Get config.
    $config = \Drupal::config('geolocation_geocodio.settings');
    $fields = $config->get('fields');
    $location = [];

    // Set up connection to geocod.io.
    $geocoder = new GeocodioAPI();
    $key = KeyProvider::getKeyValue($config->get('api_key'));
    $geocoder->setApiKey($key);

    // Attempt to geolocate address.
    try {
      // If fields are defined in settings pull associated
      // metadata.
      if (!empty($fields)) {
        $fields = explode(',', $fields);
        $result = $geocoder->geocode($address, $fields);
      }
      // Otherwise do a stright query.
      else {
        $result = $geocoder->geocode($address);
      }
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    /** @var array{results: array<int, array<string, mixed>>} $result */
    $results = $result['results'][0] ?? FALSE;

    // If no results, return false.
    if (!$results) {
      return NULL;
    }
    // Otherwise add location, formatted address and fields.
    else {
      $location['location'] = [
        'lat' => $results['location']['lat'],
        'lng' => $results['location']['lng'],
      ];
    }
    // Add formatted address if it exists.
    if (!empty($results['formatted_address'])) {
      $location['address'] = $results['formatted_address'];
    }
    // Add metadata coming from fields if it exists.
    if (!empty($results['fields'])) {
      $location['metadata'] = $results['fields'];
    }

    return $location;
  }

}
