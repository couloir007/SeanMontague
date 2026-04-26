<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Builds LodgingBusiness JSON-LD for geo_entity lodging bundle.
 *
 * Enriches the Blueprints-generated data array with structured address,
 * coordinates, and stay-duration data. The #lodging fragment on @id
 * distinguishes the lodging entity from its canonical page URL.
 */
class LodgingJsonLd {

  /**
   * Alters the JSON-LD data array for a lodging geo_entity.
   *
   * @param array $data
   *   The JSON-LD data array, passed by reference.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The geo_entity:lodging entity being processed.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Cacheability metadata.
   */
  public static function alter(array &$data, EntityInterface $entity, BubbleableMetadata $bubbleable_metadata): void {
    $data['@type'] = 'LodgingBusiness';
    $data['@id']   = static::buildId($entity);

    $geo = static::buildGeo($entity);
    if ($geo) {
      $data['geo'] = $geo;
    }

    $address = static::buildAddress($entity);
    if ($address) {
      $data['address'] = $address;
    }

    $additional = static::buildAdditionalProperties($entity);
    if ($additional) {
      $data['additionalProperty'] = $additional;
    }
  }

  /**
   * Builds the @id URI for the lodging entity.
   *
   * Uses a #lodging fragment to identify the LodgingBusiness concept
   * separately from the canonical admin URL of the geo_entity record.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The lodging entity.
   *
   * @return string
   *   Absolute URI with #lodging fragment.
   */
  protected static function buildId(EntityInterface $entity): string {
    return $entity->toUrl('canonical', ['absolute' => TRUE])->toString() . '#lodging';
  }

  /**
   * Builds GeoCoordinates from schema_geo geofield.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The lodging entity.
   *
   * @return array|null
   *   GeoCoordinates array, or NULL if field absent or empty.
   */
  protected static function buildGeo(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_geo') || $entity->get('schema_geo')->isEmpty()) {
      return NULL;
    }
    $item = $entity->get('schema_geo')->first();
    return [
      '@type'     => 'GeoCoordinates',
      'latitude'  => (float) $item->lat,
      'longitude' => (float) $item->lon,
    ];
  }

  /**
   * Builds a PostalAddress from schema_address.
   *
   * Address sub-fields are accessed as direct properties on the address
   * field item — not via ->get()->getValue() which returns arrays.
   * Returns NULL if the field is absent, empty, or yields no populated
   * sub-fields beyond @type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The lodging entity.
   *
   * @return array|null
   *   PostalAddress array, or NULL if no address data present.
   */
  protected static function buildAddress(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_address') || $entity->get('schema_address')->isEmpty()) {
      return NULL;
    }
    $item = $entity->get('schema_address')->first();
    $out  = ['@type' => 'PostalAddress'];

    if ($item->country_code) {
      $out['addressCountry'] = $item->country_code;
    }
    if ($item->locality) {
      $out['addressLocality'] = $item->locality;
    }
    if ($item->administrative_area) {
      $out['addressRegion'] = $item->administrative_area;
    }
    if ($item->postal_code) {
      $out['postalCode'] = $item->postal_code;
    }

    $street = trim(implode(', ', array_filter([$item->address_line1, $item->address_line2])));
    if ($street) {
      $out['streetAddress'] = $street;
    }

    // Only return if at least one property beyond @type was populated.
    return count($out) > 1 ? $out : NULL;
  }

  /**
   * Builds additionalProperty entries for stay metadata.
   *
   * Currently exposes field_nights as a PropertyValue so structured data
   * consumers can read the length of stay without parsing the description.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The lodging entity.
   *
   * @return array|null
   *   Array of PropertyValue arrays, or NULL if field absent or empty.
   */
  protected static function buildAdditionalProperties(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('field_nights') || $entity->get('field_nights')->isEmpty()) {
      return NULL;
    }
    return [
      [
        '@type' => 'PropertyValue',
        'name'  => 'nights',
        'value' => (int) $entity->get('field_nights')->value,
      ],
    ];
  }

}
