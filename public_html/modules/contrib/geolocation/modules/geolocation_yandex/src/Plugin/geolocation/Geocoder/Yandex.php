<?php

namespace Drupal\geolocation_yandex\Plugin\geolocation\Geocoder;

use Drupal\geolocation\Attribute\Geocoder;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\geolocation\GeocoderBase;
use Drupal\geolocation\GeocoderInterface;
use Drupal\geolocation_yandex\Plugin\geolocation\MapProvider\Yandex as YandexMapProvider;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides the Yandex.
 */
#[Geocoder(
  id: 'yandex',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Yandex'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://tech.yandex.com/maps/doc/geocoder/desc/concepts/about-docpage/ for details.'),
  locationCapable: TRUE,
  boundaryCapable: TRUE,
  frontendCapable: TRUE
)]
class Yandex extends GeocoderBase implements GeocoderInterface {

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array &$render_array, $identifier): ?array {
    parent::alterRenderArray($render_array, $identifier);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'geocoder' => [
              $this->getPluginId() => [],
            ],
          ],
        ],
      ]
    );

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address): ?array {
    if (empty($address)) {
      return NULL;
    }

    $config = \Drupal::config('geolocation_yandex.settings');

    $url = Url::fromUri('https://geocode-maps.yandex.ru/1.x/', [
      'query' => [
        'geocode' => $address,
        'format' => 'json',
        'apikey' => $config->get('api_key'),
        'lang' => YandexMapProvider::getApiUrlLangcode(),
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

    if (empty($result['response']['GeoObjectCollection']['featureMember'][0])) {
      return NULL;
    }
    else {
      $coordinates = explode(' ', $result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']);
      $location['location'] = [
        'lat' => $coordinates[1],
        'lng' => $coordinates[0],
      ];
    }

    if (!empty($result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['boundedBy']['Envelope'])) {
      $lowerCoordinates = explode(' ', $result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['boundedBy']['Envelope']['lowerCorner']);
      $upperCoordinates = explode(' ', $result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['boundedBy']['Envelope']['upperCorner']);
      $location['boundary'] = [
        'lat_north_east' => $upperCoordinates[1],
        'lng_north_east' => $upperCoordinates[0],
        'lat_south_west' => $lowerCoordinates[1],
        'lng_south_west' => $lowerCoordinates[0],
      ];
    }

    if (!empty($result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['description'])) {
      $location['address'] = $result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['description'];
    }

    return $location;
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode(float $latitude, float $longitude): ?array {
    $config = \Drupal::config('geolocation_yandex.settings');

    $url = Url::fromUri('https://geocode-maps.yandex.ru/1.x/', [
      'query' => [
        'geocode' => $longitude . "," . $latitude,
        'format' => 'json',
        'apikey' => $config->get('api_key'),
        'lang' => YandexMapProvider::getApiUrlLangcode(),
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

    if (empty($result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'])) {
      return NULL;
    }

    $data = $result['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'];

    // @todo $data['boundedBy']
    // @todo $data['Point']['pos']
    if (empty($data['metaDataProperty']['GeocoderMetaData']['Address']['Components'])) {
      return NULL;
    }

    $address_atomics = [
      'countryCode' => mb_strtolower($data['metaDataProperty']['GeocoderMetaData']['Address']['country_code']),
      'formatted_address' => $data['metaDataProperty']['GeocoderMetaData']['Address']['formatted'],
    ];

    foreach ($data['metaDataProperty']['GeocoderMetaData']['Address']['Components'] as $component) {
      switch ($component['kind']) {
        case 'country':
          $address_atomics['country'] = $component['name'];
          break;

        case 'province':
          $address_atomics['administrative_area'] = $component['name'];
          break;

        case 'locality':
          $address_atomics['locality'] = $component['name'];
          break;

        case 'street':
          $address_atomics['street'] = $component['name'];
          break;

        case 'house':
          $address_atomics['number'] = $component['name'];
          break;

      }
    }

    return [
      'atomics' => $address_atomics,
      'elements' => $this->addressElements($address_atomics),
      'formatted_address' => empty($result['display_name']) ? '' : $result['display_name'],
    ];
  }

}
