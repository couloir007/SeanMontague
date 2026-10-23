<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\Geocoder;

use Drupal\geolocation\Attribute\Geocoder;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\geolocation\GeocoderBase;
use Drupal\geolocation\GeocoderInterface;
use GuzzleHttp\Exception\TransferException;

/**
 * Provides the Photon.
 */
#[Geocoder(
  id: 'photon',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Photon'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://photon.komoot.io for details.'),
  locationCapable: TRUE,
  boundaryCapable: TRUE,
  frontendCapable: TRUE,
  reverseCapable: TRUE
)]
class Photon extends GeocoderBase implements GeocoderInterface {

  /**
   * Base URL.
   *
   * @var string
   *   Photon URL.
   */
  public string $requestBaseUrl = 'https://photon.komoot.io';

  /**
   * {@inheritdoc}
   */
  protected function getDefaultSettings(): array {
    $default_settings = parent::getDefaultSettings();

    $default_settings['location_priority'] = [
      'lat' => '',
      'lng' => '',
    ];

    $default_settings['bbox'] = [
      'lat_north_east' => '',
      'lng_north_east' => '',
      'lat_south_west' => '',
      'lng_south_west' => '',
    ];

    $default_settings['remove_duplicates'] = FALSE;

    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm(): array {

    $settings = $this->getSettings();

    $form = parent::getOptionsForm();

    $form['location_priority'] = [
      '#type' => 'geolocation_input',
      '#title' => $this->t('Location Priority'),
      '#default_value' => [
        'lat' => $settings['location_priority']['lat'],
        'lng' => $settings['location_priority']['lng'],
      ],
    ];

    $form['bbox'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bounding box'),
      '#description' => $this->t('Unlike the location priority above, this is a hard filter. Addresses outside the box yield no result at all. Leave any value empty to search worldwide.'),
      'lat_north_east' => [
        '#type' => 'number',
        '#title' => $this->t('North East latitude'),
        '#default_value' => $settings['bbox']['lat_north_east'],
        '#min' => -90,
        '#max' => 90,
        '#step' => 'any',
      ],
      'lng_north_east' => [
        '#type' => 'number',
        '#title' => $this->t('North East longitude'),
        '#default_value' => $settings['bbox']['lng_north_east'],
        '#min' => -180,
        '#max' => 180,
        '#step' => 'any',
      ],
      'lat_south_west' => [
        '#type' => 'number',
        '#title' => $this->t('South West latitude'),
        '#default_value' => $settings['bbox']['lat_south_west'],
        '#min' => -90,
        '#max' => 90,
        '#step' => 'any',
      ],
      'lng_south_west' => [
        '#type' => 'number',
        '#title' => $this->t('South West longitude'),
        '#default_value' => $settings['bbox']['lng_south_west'],
        '#min' => -180,
        '#max' => 180,
        '#step' => 'any',
      ],
    ];

    $form['remove_duplicates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove duplicates from the Photon API'),
      '#default_value' => $settings['remove_duplicates'],
      '#description' => $this->t('The Photon API can generate duplicates for some locations (i.e. cities that are states for example), this option will remove them.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode(string $address): ?array {
    if (empty($address)) {
      return NULL;
    }

    $options = [
      'q' => $address,
      'limit' => 1,
    ];

    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if (in_array($lang, ['de', 'en', 'fr'])) {
      $options['lang'] = $lang;
    }

    $settings = $this->getSettings();

    if (
      is_numeric($settings['location_priority']['lat'])
      && is_numeric($settings['location_priority']['lng'])
    ) {
      $options['lat'] = $settings['location_priority']['lat'];
      $options['lon'] = $settings['location_priority']['lng'];
    }

    if (
      is_numeric($settings['bbox']['lng_south_west'])
      && is_numeric($settings['bbox']['lat_south_west'])
      && is_numeric($settings['bbox']['lng_north_east'])
      && is_numeric($settings['bbox']['lat_north_east'])
    ) {
      $options['bbox'] = implode(',', [
        $settings['bbox']['lng_south_west'],
        $settings['bbox']['lat_south_west'],
        $settings['bbox']['lng_north_east'],
        $settings['bbox']['lat_north_east'],
      ]);
    }

    $url = Url::fromUri($this->requestBaseUrl . '/api/', [
      'query' => $options,
    ]);

    try {
      $result = Json::decode(\Drupal::httpClient()->get($url->toString())->getBody());
    }
    catch (TransferException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    $location = [];

    if (empty($result['features'][0])) {
      return NULL;
    }
    else {
      $location['location'] = [
        'lat' => $result['features'][0]['geometry']['coordinates'][1],
        'lng' => $result['features'][0]['geometry']['coordinates'][0],
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
    $url = Url::fromUri($this->requestBaseUrl . '/reverse', [
      'query' => [
        'lat' => $latitude,
        'lon' => $longitude,
        'limit' => 20,
      ],
    ]);

    try {
      $result = Json::decode(\Drupal::httpClient()->get($url->toString())->getBody());
    }
    catch (TransferException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (empty($result['features'][0]['properties'])) {
      return NULL;
    }

    $countries = \Drupal::hasService('address.country_repository') ? \Drupal::service('address.country_repository')->getList() : NULL;
    $address_atomics = [];
    foreach ($result['features'] as $entry) {
      if (empty($entry['properties']['osm_type'])) {
        continue;
      }

      switch ($entry['properties']['osm_type']) {
        case 'N':
          $address_atomics = [
            'houseNumber' => $entry['properties']['housenumber'] ?? '',
            'road' => $entry['properties']['street'] ?? '',
            'city' => $entry['properties']['city'] ?? '',
            'postcode' => $entry['properties']['postcode'] ?? '',
            'state' => $entry['properties']['state'] ?? '',
            'country' => $entry['properties']['country'] ?? '',
          ];
          if ($entry['properties']['countrycode'] ?? FALSE) {
            $address_atomics['countryCode'] = $entry['properties']['countrycode'];
          }
          elseif ($countries && array_search($entry['properties']['country'], $countries)) {
            $address_atomics['countryCode'] = array_search($entry['properties']['country'], $countries);
          }
          break 2;
      }
    }

    if (empty($address_atomics)) {
      return NULL;
    }

    return [
      'atomics' => $address_atomics,
      'elements' => $this->addressElements($address_atomics),
      'formatted_address' => empty($result['display_name']) ? '' : $result['display_name'],
    ];
  }

}
