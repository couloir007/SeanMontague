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
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('field_geo') || $entity->get('field_geo')->isEmpty()) {
      return NULL;
    }
    $item = $entity->get('field_geo')->first();
    return [
      '@type' => 'GeoCoordinates',
      'latitude' => $item->get('lat')->getValue(),
      'longitude' => $item->get('lon')->getValue(),
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
    return [
      '@type' => 'Place',
      'name' => $place->label(),
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
