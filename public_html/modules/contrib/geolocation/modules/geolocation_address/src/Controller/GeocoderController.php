<?php

namespace Drupal\geolocation_address\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\geolocation\GeocoderManager;
use Drupal\geolocation\GeolocationAddress;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AddressWidgetController.
 *
 * @package Drupal\geolocation_address\Controller
 */
class GeocoderController extends ControllerBase {

  /**
   * Geocoder Manager.
   *
   * @var \Drupal\geolocation\GeocoderManager
   */
  protected GeocoderManager $geocoderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.geolocation.geocoder')
    );
  }

  /**
   * Geocoder Controller.
   *
   * @param \Drupal\geolocation\GeocoderManager $geocoder_manager
   *   Geocoder Manager.
   */
  public function __construct(GeocoderManager $geocoder_manager) {
    $this->geocoderManager = $geocoder_manager;
  }

  /**
   * Return coordinates.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Geocoded coordinates.
   */
  public function geocode(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['geocoder'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    if (empty($data['address'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    $geocoder = $this->geocoderManager->getGeocoder($data['geocoder'], $data['geocoder_settings'] ?? []);
    if (is_string($data['address'])) {
      $geocoded_result = $geocoder->geocode($data['address']);
    }
    elseif (is_array($data['address'])) {
      $geocoded_result = $geocoder->geocodeAddress(new GeolocationAddress(
        organization: $data['address']['organization'] ?? '',
        addressLine1: $data['address']['addressLine1'] ?? '',
        addressLine2: $data['address']['addressLine2'] ?? '',
        addressLine3: $data['address']['addressLine3'] ?? '',
        dependentLocality: $data['address']['dependentLocality'] ?? '',
        locality: $data['address']['locality'] ?? '',
        administrativeArea: $data['address']['administrativeArea'] ?? '',
        postalCode: $data['address']['postalCode'] ?? '',
        sortingCode: $data['address']['sortingCode'] ?? '',
        countryCode: $data['address']['countryCode'] ?? '',
      ));
    }
    else {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    if (!isset($geocoded_result['location'])) {
      return new JsonResponse([], Response::HTTP_NOT_FOUND);
    }
    return new JsonResponse($geocoded_result['location']);
  }

  /**
   * Return formatted address data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Formatted address.
   */
  public function reverse(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['geocoder'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    if (empty($data['geocoder_settings'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    $geocoder = $this->geocoderManager->getGeocoder($data['geocoder'], $data['geocoder_settings']);

    if (!$data['latitude'] || !$data['longitude']) {
      return new JsonResponse(FALSE);
    }

    $address = $geocoder->reverseGeocode($data['latitude'], $data['longitude']);
    if (empty($address['elements']['countryCode'])) {
      return new JsonResponse(FALSE);
    }

    return new JsonResponse($address['elements']);
  }

}
