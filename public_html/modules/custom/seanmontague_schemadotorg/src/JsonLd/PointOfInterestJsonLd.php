<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Builds TouristAttraction JSON-LD for geo_entity poi bundle.
 *
 * Enriches the Blueprints-generated data array with coordinates,
 * containedInPlace, and image data. The #poi fragment on @id distinguishes
 * the attraction concept from the geo_entity's canonical admin URL.
 *
 * containedInPlace resolves to TouristDestination when the referenced
 * schema_place node is a tourist_destination bundle, falling back to Place
 * for generic place nodes (e.g. Kingdom Trails, Burke Mountain).
 */
class PointOfInterestJsonLd {

  /**
   * Alters the JSON-LD data array for a poi geo_entity.
   *
   * @param array $data
   *   The JSON-LD data array, passed by reference.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The geo_entity:poi entity being processed.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Cacheability metadata.
   */
  public static function alter(array &$data, EntityInterface $entity, BubbleableMetadata $bubbleable_metadata): void {
    $data['@type'] = 'TouristAttraction';
    $data['@id']   = static::buildId($entity);

    $geo = static::buildGeo($entity);
    if ($geo) {
      $data['geo'] = $geo;
    }

    $contained = static::buildContainedInPlace($entity);
    if ($contained) {
      $data['containedInPlace'] = $contained;
    }

    $image = static::buildImage($entity);
    if ($image) {
      $data['image'] = $image;
    }
  }

  /**
   * Builds the @id URI for the POI entity.
   *
   * Uses a #poi fragment to identify the TouristAttraction concept
   * separately from the canonical admin URL of the geo_entity record.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The POI entity.
   *
   * @return string
   *   Absolute URI with #poi fragment.
   */
  protected static function buildId(EntityInterface $entity): string {
    return $entity->toUrl('canonical', ['absolute' => TRUE])->toString() . '#poi';
  }

  /**
   * Builds GeoCoordinates from schema_geo geofield.
   *
   * Geofield lat/lon are accessed as direct properties on the field item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The POI entity.
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
   * Builds containedInPlace from the schema_place entity reference.
   *
   * Resolves to TouristDestination when the referenced node is a
   * tourist_destination bundle (e.g. Galway, Dublin), or falls back to
   * Place for generic place nodes (e.g. Kingdom Trails, Burke Mountain).
   * Includes @id so Google can follow the reference to the destination page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The POI entity.
   *
   * @return array|null
   *   containedInPlace array, or NULL if field absent, empty, or unresolvable.
   */
  protected static function buildContainedInPlace(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_place') || $entity->get('schema_place')->isEmpty()) {
      return NULL;
    }
    $place = $entity->get('schema_place')->entity;
    if (!$place) {
      return NULL;
    }
    $type = $place->bundle() === 'tourist_destination' ? 'TouristDestination' : 'Place';
    return [
      '@type' => $type,
      'name'  => $place->label(),
      '@id'   => $place->toUrl('canonical', ['absolute' => TRUE])->toString(),
    ];
  }

  /**
   * Builds an ImageObject from the schema_image media entity reference.
   *
   * Traverses: schema_image → media entity → source field → file entity → URL.
   * Uses getSource() to resolve the media source field dynamically rather than
   * hardcoding field_media_image, keeping this compatible with any image media
   * type configuration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The POI entity.
   *
   * @return array|null
   *   ImageObject array, or NULL if field absent, empty, or unresolvable.
   */
  protected static function buildImage(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_image') || $entity->get('schema_image')->isEmpty()) {
      return NULL;
    }
    $media = $entity->get('schema_image')->entity;
    if (!$media) {
      return NULL;
    }
    $source       = $media->getSource();
    $source_field = $source->getSourceFieldDefinition($media->bundle->entity)->getName();
    if ($media->get($source_field)->isEmpty()) {
      return NULL;
    }
    $file = $media->get($source_field)->entity;
    if (!$file) {
      return NULL;
    }
    return [
      '@type' => 'ImageObject',
      'url'   => $file->createFileUrl(FALSE),
    ];
  }

}
