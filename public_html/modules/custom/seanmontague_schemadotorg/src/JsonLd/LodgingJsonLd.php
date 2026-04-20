<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Builds LodgingBusiness JSON-LD for geo_entity lodging bundle.
 */
class LodgingJsonLd {

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

  protected static function buildId(EntityInterface $entity): string {
    return $entity->toUrl('canonical', ['absolute' => TRUE])->toString() . '#lodging';
  }

  protected static function buildGeo(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_geo') || $entity->get('schema_geo')->isEmpty()) {
      return NULL;
    }
    $item = $entity->get('schema_geo')->first();
    return [
      '@type'     => 'GeoCoordinates',
      'latitude'  => (float) $item->get('lat')->getValue(),
      'longitude' => (float) $item->get('lon')->getValue(),
    ];
  }

  protected static function buildAddress(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_address') || $entity->get('schema_address')->isEmpty()) {
      return NULL;
    }
    $item = $entity->get('schema_address')->first();
    $out  = ['@type' => 'PostalAddress'];

    $country = $item->get('country_code')->getValue();
    if ($country) {
      $out['addressCountry'] = $country;
    }

    $locality = $item->get('locality')->getValue();
    if ($locality) {
      $out['addressLocality'] = $locality;
    }

    $region = $item->get('administrative_area')->getValue();
    if ($region) {
      $out['addressRegion'] = $region;
    }

    $postal = $item->get('postal_code')->getValue();
    if ($postal) {
      $out['postalCode'] = $postal;
    }

    $line1  = $item->get('address_line1')->getValue();
    $line2  = $item->get('address_line2')->getValue();
    $street = trim(implode(', ', array_filter([$line1, $line2])));
    if ($street) {
      $out['streetAddress'] = $street;
    }

    // Only return if at least one property beyond @type was populated.
    return count($out) > 1 ? $out : NULL;
  }

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
