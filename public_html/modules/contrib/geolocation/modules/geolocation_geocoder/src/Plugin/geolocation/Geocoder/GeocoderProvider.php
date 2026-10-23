<?php

namespace Drupal\geolocation_geocoder\Plugin\geolocation\Geocoder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\geocoder\GeocoderInterface as GeocoderServiceInterface;
use Drupal\geocoder\GeocoderProviderInterface;
use Drupal\geocoder\ProviderPluginManager;
use Drupal\geolocation\Attribute\Geocoder;
use Drupal\geolocation\GeocoderBase;
use Drupal\geolocation\GeocoderCountryFormattingManager;
use Drupal\geolocation\GeocoderInterface;
use Geocoder\Collection;
use Geocoder\Location;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides geocoding by the Geocoder module.
 */
#[Geocoder(
  id: 'geocoder_provider',
  name: new TranslatableMarkup('Geocoder module'),
  description: new TranslatableMarkup('Geocode by providers configured in the Geocoder module.'),
  locationCapable: TRUE,
  boundaryCapable: TRUE,
  frontendCapable: FALSE,
  reverseCapable: TRUE
)]
class GeocoderProvider extends GeocoderBase implements GeocoderInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GeocoderCountryFormattingManager $country_formatter_manager,
    ModuleHandlerInterface $module_handler,
    FileSystemInterface $file_system,
    protected GeocoderServiceInterface $geocoder,
    protected ProviderPluginManager $providerPluginManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $country_formatter_manager, $module_handler, $file_system);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): GeocoderInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.geocoder_country_formatting'),
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('geocoder'),
      $container->get('plugin.manager.geocoder.provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultSettings(): array {
    return parent::getDefaultSettings() + [
      'providers' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm(): array {
    $form = parent::getOptionsForm();

    $form['providers'] = $this->providerPluginManager->providersPluginsTableList($this->getEnabledProviderIds());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode(string $address): ?array {
    if (empty($address)) {
      return NULL;
    }

    $providers = $this->getProviders();
    if (empty($providers)) {
      return NULL;
    }

    $location = $this->getFirstResult($this->geocoder->geocode($address, $providers));
    if (!$location) {
      return NULL;
    }

    return $this->getLocation($location);
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode(float $latitude, float $longitude): ?array {
    $providers = $this->getProviders();
    if (empty($providers)) {
      return NULL;
    }

    $location = $this->getFirstResult($this->geocoder->reverse((string) $latitude, (string) $longitude, $providers));
    if (!$location) {
      return NULL;
    }

    $address_atomics = $this->getAddressAtomics($location);

    return [
      'atomics' => $address_atomics,
      'elements' => $this->addressElements($address_atomics),
      'formatted_address' => $this->getFormattedAddress($address_atomics),
    ];
  }

  /**
   * Get the enabled providers in their configured order.
   *
   * The options form stores the entire provider table, so unchecked providers
   * have to be filtered out and the remainder sorted by weight.
   *
   * @return string[]
   *   Geocoder provider entity IDs.
   */
  protected function getEnabledProviderIds(): array {
    $providers = array_filter(
      $this->getSettings()['providers'] ?? [],
      fn (array $provider): bool => !empty($provider['checked'])
    );

    uasort($providers, fn (array $a, array $b): int => ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0));

    return array_keys($providers);
  }

  /**
   * Load the configured Geocoder provider entities.
   *
   * @return \Drupal\geocoder\GeocoderProviderInterface[]
   *   Geocoder providers, in the configured order.
   */
  protected function getProviders(): array {
    $provider_ids = $this->getEnabledProviderIds();
    if (empty($provider_ids)) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('geocoder_provider');

    $providers = [];
    foreach ($provider_ids as $provider_id) {
      $provider = $storage->load($provider_id);
      if ($provider instanceof GeocoderProviderInterface) {
        $providers[$provider_id] = $provider;
      }
    }

    return $providers;
  }

  /**
   * Get the best result of a Geocoder response.
   *
   * Providers return either a collection of locations or, for the file based
   * ones, a geometry this plugin cannot handle.
   *
   * @param mixed $result
   *   Geocoder response.
   *
   * @return \Geocoder\Location|null
   *   Best result or NULL.
   */
  protected function getFirstResult(mixed $result): ?Location {
    if (
      !$result instanceof Collection
      || $result->isEmpty()
    ) {
      return NULL;
    }

    return $result->first();
  }

  /**
   * Convert a Geocoder result to a geolocation location.
   *
   * @param \Geocoder\Location $location
   *   Geocoder result.
   *
   * @return array|null
   *   Location or NULL.
   */
  protected function getLocation(Location $location): ?array {
    $coordinates = $location->getCoordinates();
    if (!$coordinates) {
      return NULL;
    }

    $return = [
      'location' => [
        'lat' => $coordinates->getLatitude(),
        'lng' => $coordinates->getLongitude(),
      ],
    ];

    $bounds = $location->getBounds();
    if ($bounds) {
      $return['boundary'] = [
        'lat_north_east' => $bounds->getNorth(),
        'lng_north_east' => $bounds->getEast(),
        'lat_south_west' => $bounds->getSouth(),
        'lng_south_west' => $bounds->getWest(),
      ];
    }

    $address = $this->getFormattedAddress($this->getAddressAtomics($location));
    if ($address) {
      $return['address'] = $address;
    }

    return $return;
  }

  /**
   * Convert a Geocoder result to address atomics.
   *
   * @param \Geocoder\Location $location
   *   Geocoder result.
   *
   * @return array
   *   Address atomics.
   */
  protected function getAddressAtomics(Location $location): array {
    $country = $location->getCountry();

    $address_atomics = [
      'houseNumber' => $location->getStreetNumber(),
      'road' => $location->getStreetName(),
      'suburb' => $location->getSubLocality(),
      'city' => $location->getLocality(),
      'postcode' => $location->getPostalCode(),
      'country' => $country?->getName(),
      'countryCode' => $country?->getCode() ? strtoupper($country->getCode()) : NULL,
    ];

    foreach ($location->getAdminLevels() as $admin_level) {
      switch ($admin_level->getLevel()) {
        case 1:
          $address_atomics['state'] = $admin_level->getName();
          $address_atomics['stateCode'] = $admin_level->getCode();
          break;

        case 2:
          $address_atomics['county'] = $admin_level->getName();
          $address_atomics['countyCode'] = $admin_level->getCode();
          break;
      }
    }

    return array_filter($address_atomics, fn ($value): bool => !is_null($value) && $value !== '');
  }

  /**
   * Build a single line address.
   *
   * Geocoder results carry no formatted address, so it is assembled from the
   * atomics available.
   *
   * @param array $address_atomics
   *   Address atomics.
   *
   * @return string
   *   Formatted address.
   */
  protected function getFormattedAddress(array $address_atomics): string {
    $street = implode(' ', array_filter([
      $address_atomics['road'] ?? NULL,
      $address_atomics['houseNumber'] ?? NULL,
    ]));

    $locality = implode(' ', array_filter([
      $address_atomics['postcode'] ?? NULL,
      $address_atomics['city'] ?? $address_atomics['county'] ?? NULL,
    ]));

    return implode(', ', array_filter([
      $street,
      $locality,
      $address_atomics['state'] ?? NULL,
      $address_atomics['country'] ?? NULL,
    ]));
  }

}
