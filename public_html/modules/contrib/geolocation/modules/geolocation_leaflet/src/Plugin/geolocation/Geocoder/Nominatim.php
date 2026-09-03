<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\Geocoder;

use Drupal\geolocation\Attribute\Geocoder;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\geolocation\GeocoderBase;
use Drupal\geolocation\GeocoderInterface;
use Drupal\geolocation\GeolocationAddress;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides the Nominatim API.
 */
#[Geocoder(
  id: 'nominatim',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Nominatim'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://wiki.openstreetmap.org/wiki/Nominatim for details.'),
  locationCapable: TRUE,
  boundaryCapable: TRUE,
  frontendCapable: FALSE,
  reverseCapable: TRUE
)]
class Nominatim extends GeocoderBase implements GeocoderInterface {

  /**
   * Nominatim base URL.
   *
   * @var string
   */
  protected static string $nominatimGeocodingUrl = 'https://nominatim.openstreetmap.org/search';

  /**
   * Nominatim reverse geocoding URL.
   *
   * @var string
   */
  protected static string $nominatimReverseGeocodingUrl = 'https://nominatim.openstreetmap.org/reverse';

  /**
   * {@inheritdoc}
   */
  public function geocode(string $address): ?array {
    if (empty($address)) {
      return NULL;
    }

    $url = Url::fromUri($this->getGeocodingUrl(), [
      'query' => [
        'q' => $address,
        'email' => $this->getRequestEmail(),
        'limit' => 1,
        'format' => 'json',
        'connect_timeout' => 5,
      ],
    ]);

    try {
      $result = Json::decode(\Drupal::httpClient()->get($url->toString())->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    $location = [];

    if (empty($result[0])) {
      return NULL;
    }
    else {
      $location['location'] = [
        'lat' => $result[0]['lat'],
        'lng' => $result[0]['lon'],
      ];
    }

    if (!empty($result[0]['boundingbox'])) {
      $location['boundary'] = [
        'lat_north_east' => $result[0]['boundingbox'][1],
        'lng_north_east' => $result[0]['boundingbox'][3],
        'lat_south_west' => $result[0]['boundingbox'][0],
        'lng_south_west' => $result[0]['boundingbox'][2],
      ];
    }

    if (!empty($result[0]['display_name'])) {
      $location['address'] = $result[0]['display_name'];
    }

    return $location;
  }

  /**
   * Geocode a structured address.
   */
  public function geocodeAddress(GeolocationAddress $address): ?array {
    $url = Url::fromUri($this->getGeocodingUrl(), [
      'query' => [
        'street' => $address->addressLine1,
        'city' => $address->locality ?? NULL,
        'county' => $address->dependentLocality ?? NULL,
        'state' => $address->administrativeArea ?? NULL,
        'country' => $address->countryCode ?? NULL,
        'postalcode' => $address->postalCode ?? NULL,
        'limit' => 1,
        'format' => 'json',
        'connect_timeout' => 5,
      ],
    ]);

    try {
      $result = Json::decode(\Drupal::httpClient()->get($url->toString())->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    $location = [];

    if (empty($result[0])) {
      return NULL;
    }
    else {
      $location['location'] = [
        'lat' => $result[0]['lat'],
        'lng' => $result[0]['lon'],
      ];
    }

    if (!empty($result[0]['boundingbox'])) {
      $location['boundary'] = [
        'lat_north_east' => $result[0]['boundingbox'][1],
        'lng_north_east' => $result[0]['boundingbox'][3],
        'lat_south_west' => $result[0]['boundingbox'][0],
        'lng_south_west' => $result[0]['boundingbox'][2],
      ];
    }

    if (!empty($result[0]['display_name'])) {
      $location['address'] = $result[0]['display_name'];
    }

    return $location;
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode(float $latitude, float $longitude): ?array {
    $url = Url::fromUri($this->getReverseGeocodingUrl(), [
      'query' => [
        'lat' => $latitude,
        'lon' => $longitude,
        'email' => $this->getRequestEmail(),
        'limit' => 1,
        'format' => 'json',
        'connect_timeout' => 5,
        'addressdetails' => 1,
        'zoom' => 18,
      ],
    ]);

    try {
      $result = Json::decode(\Drupal::httpClient()->get($url->toString())->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (empty($result['address'])) {
      return NULL;
    }

    $address_atomics = [];
    foreach ($result['address'] as $component => $value) {
      switch ($component) {
        case 'house_number':
          $address_atomics['houseNumber'] = $value;
          break;

        case 'road':
          $address_atomics['road'] = $value;
          break;

        case 'town':
          $address_atomics['village'] = $value;
          break;

        case 'city':
          $address_atomics['city'] = $value;
          break;

        case 'county':
          $address_atomics['county'] = $value;
          break;

        case 'postcode':
          $address_atomics['postcode'] = $value;
          break;

        case 'state':
          $address_atomics['state'] = $value;
          break;

        case 'country':
          $address_atomics['country'] = $value;
          break;

        case 'country_code':
          $address_atomics['countryCode'] = strtoupper($value);
          break;

        case 'suburb':
          $address_atomics['suburb'] = $value;
          break;

        case 'ISO3166-2-lvl6':
          $address_atomics['countyCode'] = $value;
          break;
      }
    }

    return [
      'atomics' => $address_atomics,
      'elements' => $this->addressElements($address_atomics),
      'formatted_address' => empty($result['display_name']) ? '' : $result['display_name'],
    ];
  }

  /**
   * Retrieve base URL from setting or default.
   *
   * @return string
   *   Base URL.
   */
  protected function getGeocodingUrl(): string {
    $config = \Drupal::config('geolocation_leaflet.nominatim_settings');

    if (!empty($config->get('nominatim_geocoding_url'))) {
      $request_url = $config->get('nominatim_geocoding_url');
    }
    else {
      $request_url = self::$nominatimGeocodingUrl;
    }
    return $request_url;
  }

  /**
   * Retrieve base URL from setting or default.
   *
   * @return string
   *   Base URL.
   */
  protected function getReverseGeocodingUrl(): string {
    $config = \Drupal::config('geolocation_leaflet.nominatim_settings');

    if (!empty($config->get('nominatim_reverse_geocoding_url'))) {
      $request_url = $config->get('nominatim_reverse_geocoding_url');
    }
    else {
      $request_url = self::$nominatimReverseGeocodingUrl;
    }
    return $request_url;
  }

  /**
   * Nominatim should be called with a request E-Mail.
   *
   * @return string
   *   Get Request Email.
   */
  protected function getRequestEmail(): string {
    $config = \Drupal::config('geolocation_leaflet.nominatim_settings');

    if (!empty($config->get('nominatim_email'))) {
      $request_email = $config->get('nominatim_email');
    }
    else {
      $request_email = \Drupal::config('system.site')->get('mail');
    }
    return $request_email;
  }

}
