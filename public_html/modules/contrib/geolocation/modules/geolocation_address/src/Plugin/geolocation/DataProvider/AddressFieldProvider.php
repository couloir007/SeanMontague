<?php

namespace Drupal\geolocation_address\Plugin\geolocation\DataProvider;

use Drupal\geolocation\Attribute\DataProvider;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Utility\Token;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
use Drupal\geolocation\GeocoderInterface;
use Drupal\geolocation\GeocoderManager;
use Drupal\geolocation\GeolocationAddress;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides default address field.
 */
#[DataProvider(
  id: 'geolocation_address_field_provider',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Address Field'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Address Field.')
)]
class AddressFieldProvider extends DataProviderBase implements DataProviderInterface {

  /**
   * Geocoder for address resolution.
   *
   * @var \Drupal\geolocation\GeocoderInterface
   */
  protected GeocoderInterface $geocoder;

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFieldManagerInterface $entity_field_manager,
    ModuleHandlerInterface $moduleHandler,
    Token $token,
    protected GeocoderManager $geocoderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager, $moduleHandler, $token);
    if (!empty($configuration['geocoder'])) {
      $this->geocoder = $this->geocoderManager->createInstance($configuration['geocoder']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): DataProviderInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('plugin.manager.geolocation.geocoder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isViewsGeoOption(FieldPluginBase $viewsField): bool {
    if ($viewsField instanceof EntityField) {

      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
      $entityFieldManager = \Drupal::service('entity_field.manager');

      $field_map = $entityFieldManager->getFieldMap();

      if (
        !empty($field_map)
        &&!empty($viewsField->configuration['entity_type'])
        && !empty($viewsField->configuration['field_name'])
        && !empty($field_map[$viewsField->configuration['entity_type']])
        && !empty($field_map[$viewsField->configuration['entity_type']][$viewsField->configuration['field_name']])
      ) {
        if ($field_map[$viewsField->configuration['entity_type']][$viewsField->configuration['field_name']]['type'] == 'address') {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool {
    return ($fieldDefinition->getType() == 'address');
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsFromItem(FieldItemInterface $fieldItem): array {
    if (!($fieldItem instanceof AddressItem)) {
      return [];
    }

    if (empty($this->geocoder)) {
      return [];
    }

    $coordinates = $this->geocoder->geocodeAddress(new GeolocationAddress(
      organization: $fieldItem->getOrganization(),
      addressLine1: $fieldItem->getAddressLine1(),
      addressLine2: $fieldItem->getAddressLine2(),
      addressLine3: $fieldItem->getAddressLine3(),
      dependentLocality: $fieldItem->getDependentLocality(),
      locality: $fieldItem->getLocality(),
      administrativeArea: $fieldItem->getAdministrativeArea(),
      postalCode: $fieldItem->getPostalCode(),
      sortingCode: $fieldItem->getSortingCode(),
      countryCode: $fieldItem->getCountryCode(),
    ));

    return !empty($coordinates['location']) ? [
      '#type' => 'geolocation_map_location',
      '#coordinates' => $coordinates['location'],
    ] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $element = parent::getSettingsForm($settings, $parents);

    $geocoder_options = [];
    foreach ($this->geocoderManager->getDefinitions() as $geocoder_id => $geocoder_definition) {
      if (empty($geocoder_definition['locationCapable'])) {
        continue;
      }
      $geocoder_options[$geocoder_id] = $geocoder_definition['name'];
    }

    if (empty($geocoder_options)) {
      return [
        '#markup' => $this->t('No geocoder option found'),
      ];
    }

    $element['geocoder'] = [
      '#type' => 'select',
      '#title' => $this->t('Geocoder'),
      '#options' => $geocoder_options,
      '#default_value' => empty($settings['geocoder']) ? key($geocoder_options) : $settings['geocoder'],
      '#description' => $this->t('Choose plugin to geocode address into coordinates.'),
      '#weight' => -1,
    ];

    return $element;
  }

}
