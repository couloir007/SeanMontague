<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Builds TouristAttraction JSON-LD for geo_entity poi bundle.
 */
class PointOfInterestJsonLd {

  public static function alter(array &$data, EntityInterface $entity, BubbleableMetadata $bubbleable_metadata): void {
    $data['@type'] = 'TouristAttraction';
    $data['@id'] = static::buildId($entity);

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

  protected static function buildId(EntityInterface $entity): string {
    return $entity->toUrl('canonical', ['absolute' => TRUE])->toString() . '#poi';
  }

  protected static function buildGeo(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_geo') || $entity->get('schema_geo')->isEmpty()) {
      return NULL;
    }
    $item = $entity->get('schema_geo')->first();
    return [
      '@type' => 'GeoCoordinates',
      'latitude'  => (float) $item->lat,
      'longitude' => (float) $item->lon,
    ];
  }

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

  protected static function buildImage(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_image') || $entity->get('schema_image')->isEmpty()) {
      return NULL;
    }
    $media = $entity->get('schema_image')->entity;
    if (!$media) {
      return NULL;
    }
    $source = $media->getSource();
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
      'url' => $file->createFileUrl(FALSE),
    ];
  }

}
