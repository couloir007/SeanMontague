<?php

namespace Drupal\geolocation\Plugin\geolocation\Location;

use Drupal\geolocation\Attribute\Location;
use Drupal\geolocation\LocationBase;
use Drupal\geolocation\LocationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fixed coordinates map center.
 */
#[Location(
  id: 'ipstack',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('ipstack Service'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://ipstack.com/ website. Limited to 10000 requests per month. Access key required.')
)]
class IpStack extends LocationBase implements LocationInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected Request $request,
    protected ClientInterface $httpClient,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LocationInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'access_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(?string $location_option_id = NULL, array $settings = [], $context = NULL): array {
    $settings = $this->getSettings($settings);

    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Key'),
      '#default_value' => $settings['access_key'],
      '#size' => 60,
      '#maxlength' => 128,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates(string $location_option_id, array $location_option_settings, $context = NULL): array {
    $settings = $this->getSettings($location_option_settings);
    // Access Key is required.
    if (empty($settings['access_key'])) {
      return [];
    }

    // Get client IP and validate it is a proper IP address.
    $ip = $this->request->getClientIp();
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
      return [];
    }

    // Get data from api.ipstack.com.
    try {
      $response = $this->httpClient->request('GET', 'https://api.ipstack.com/' . rawurlencode($ip), [
        'query' => ['access_key' => $settings['access_key']],
        'timeout' => 5,
      ]);
      $json = $response->getBody()->getContents();
    }
    catch (RequestException $e) {
      return [];
    }

    if (empty($json)) {
      return [];
    }

    $result = json_decode($json, TRUE);
    if (
      empty($result)
      || empty($result['latitude'])
      || empty($result['longitude'])
    ) {
      return [];
    }

    return [
      'lat' => (float) $result['latitude'],
      'lng' => (float) $result['longitude'],
    ];
  }

}
