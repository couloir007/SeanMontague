<?php

namespace Drupal\geolocation_google_places_api\Plugin\geolocation\Geocoder;

use Drupal\geolocation\Attribute\Geocoder;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Error;
use Drupal\geolocation_google_maps\GoogleGeocoderBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides the Google Places API.
 */
#[Geocoder(
  id: 'google_places_api',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Google Places API'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Attention: This Plugin needs you to follow Google Places API TOS and either use the Attribution Block or provide it yourself.'),
  locationCapable: TRUE,
  boundaryCapable: TRUE,
  frontendCapable: TRUE,
  reverseCapable: FALSE
)]
class GooglePlacesAPI extends GoogleGeocoderBase {

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array &$render_array, string $identifier): ?array {
    parent::alterRenderArray($render_array, $identifier);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'library' => [
          'geolocation_google_places_api/geolocation_google_places_api.googleplacesicons',
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

    $config = \Drupal::config('geolocation_google_maps.settings');

    $params = ['input' => $address];

    if (!empty($this->configuration['component_restrictions']['country'])) {
      foreach (explode(',', $this->configuration['component_restrictions']['country']) as $country) {
        $params['components[]'] = 'country:' . $country;
      }
    }
    if (!empty($config->get('google_map_custom_url_parameters')['language'])) {
      $params['language'] = $config->get('google_map_custom_url_parameters')['language'];
    }

    // Adding session token as per Google Places API to combine both api calls
    // in a single session to reduce billing.
    // @see https://developers.google.com/maps/documentation/places/web-service/details#sessiontoken
    // and
    // @see https://developers.google.com/maps/documentation/places/web-service/autocomplete#sessiontoken
    // for more details.
    $session_token = \Drupal::service('uuid')->generate();
    $params['sessiontoken'] = $session_token;
    $request_url = $this->googleMapsService->getGoogleMapsApiUrl($params, '/maps/api/place/autocomplete/json');

    try {
      $result = Json::decode(\Drupal::httpClient()->request('GET', $request_url)->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['predictions'][0]['place_id'])
    ) {
      return NULL;
    }

    try {
      // Including the same session token and place_id retrieved for place
      // details API call.
      // @see https://developers.google.com/maps/documentation/places/web-service/details
      // for details.
      $params = [
        'place_id' => $result['predictions'][0]['place_id'],
        'fields' => "geometry,formatted_address",
        'sessiontoken' => $session_token,
      ];
      $details_url = $this->googleMapsService->getGoogleMapsApiUrl($params, '/maps/api/place/details/json');
      $details = Json::decode(\Drupal::httpClient()->request('GET', $details_url)->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (
      $details['status'] != 'OK'
      || empty($details['result']['geometry']['location'])
    ) {
      return NULL;
    }

    return [
      'location' => [
        'lat' => $details['result']['geometry']['location']['lat'],
        'lng' => $details['result']['geometry']['location']['lng'],
      ],
      // @todo Add viewport or build it if missing.
      'boundary' => [
        'lat_north_east' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lat'] + 0.005 : $details['result']['geometry']['viewport']['northeast']['lat'],
        'lng_north_east' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lng'] + 0.005 : $details['result']['geometry']['viewport']['northeast']['lng'],
        'lat_south_west' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lat'] - 0.005 : $details['result']['geometry']['viewport']['southwest']['lat'],
        'lng_south_west' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lng'] - 0.005 : $details['result']['geometry']['viewport']['southwest']['lng'],
      ],
      'address' => empty($details['result']['formatted_address']) ? '' : $details['result']['formatted_address'],
    ];
  }

}
