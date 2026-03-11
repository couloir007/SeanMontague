<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_address;

use Drupal\address\AddressInterface;
use Drupal\address\Repository\SubdivisionRepository;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Utility\SchemaDotOrgFieldHelper;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The Schema.org address JSON-LD manager.
 */
class SchemaDotOrgAddressJsonLdManager implements SchemaDotOrgAddressJsonLdManagerInterface {

  /**
   * Constructs a SchemaDotOrgAddressJsonLdManager object.
   *
   * @param \Drupal\address\Repository\SubdivisionRepository $subdivisionRepository
   *   The subdivision repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface|null $schemaJsonldManager
   *   The Schema.org JSON-LD manager.
   */
  public function __construct(
    #[Autowire(service: 'address.subdivision_repository')]
    protected SubdivisionRepository $subdivisionRepository,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    #[Autowire(service: 'schemadotorg_jsonld.manager')]
    protected SchemaDotOrgJsonLdManagerInterface|null $schemaJsonldManager = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void {
    $field_type = $item->getFieldDefinition()->getType();
    if ($field_type !== 'address'
      || !$item instanceof AddressInterface) {
      return;
    }

    $mapping = [
      'country_code' => 'addressCountry',
      'administrative_area' => 'addressRegion',
      'locality' => 'addressLocality',
      'dependent_locality' => 'addressLocality',
      'postal_code' => 'postalCode',
      'sorting_code' => 'postOfficeBoxNumber',
      'address_line1' => 'streetAddress',
      'address_line2' => 'streetAddress',
    ];
    $values = $item->getValue();

    // Lookup the locality's string value.
    $subdivision_list = $this->subdivisionRepository->getList([$item->getCountryCode()], $item->getLocale());
    $values['locality'] = $subdivision_list[$values['locality']] ?? $values['locality'];

    // Set default values.
    $values += [
      'organization' => '',
      'given_name' => '',
      'additional_name' => '',
      'family_name' => '',
    ];
    // Map organization and full name to Schema.org name and
    // alternateName properties.
    $values['organization'] = trim((string) $values['organization']);
    $values['name'] = implode(' ', array_filter([
      trim((string) $values['given_name']),
      trim((string) $values['additional_name']),
      trim((string) $values['family_name']),
    ]));
    if ($values['organization']) {
      $mapping['organization'] = 'name';
      $mapping['name'] = 'alternateName';
    }
    else {
      $mapping['name'] = 'name';
    }

    // Detect if the Schema.org property is looking for a
    // https://schema.org/PostalAddress or https://schema.org/Place.
    // @see https://schema.org/containsPlace
    $address_data = [];
    foreach ($mapping as $source => $destination) {
      if (!empty($values[$source])) {
        if (isset($address_data[$destination])) {
          $address_data[$destination] .= ', ' . $values[$source];
        }
        else {
          $address_data[$destination] = $values[$source];
        }
      }
    }

    // Build the JSON-LD based on the Schema.org property's range includes.
    $schema_property = SchemaDotOrgFieldHelper::getSchemaProperty($item);
    $range_includes = $this->schemaTypeManager->getPropertyRangeIncludes($schema_property);
    if (isset($range_includes['PostalAddress'])) {
      $data = ['@type' => 'PostalAddress'];
      $data += $address_data;
    }
    else {
      // Default to https://schema.org/Place.
      $data = ['@type' => 'Place'];
      $place_properties = [
        'name' => 'name',
        'alternateName' => 'alternateName',
      ];
      // Set the https://schema.org/Place properties.
      $data += array_intersect_key($address_data, $place_properties);

      // Set the https://schema.org/address
      // to https://schema.org/PostalAddress properties.
      $data['address'] = ['@type' => 'PostalAddress'];
      $data['address'] += array_diff_key($address_data, $place_properties);
    }

    $value = $this->schemaJsonldManager->sortProperties($data);
  }

}
