<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Builds BlogPosting additions for article nodes.
 */
class ArticleJsonLd {

  public static function alter(array &$data, EntityInterface $entity, BubbleableMetadata $bubbleable_metadata): void {
    $mentions = static::buildMentions($entity);
    if ($mentions) {
      $data['mentions'] = $mentions;
    }

    $spatial = static::buildSpatialCoverage($entity);
    if ($spatial) {
      $data['spatialCoverage'] = $spatial;
    }
  }

  protected static function buildMentions(EntityInterface $entity): array {
    if ($entity->get('schema_poi')->isEmpty()) {
      return [];
    }
    $mentions = [];
    foreach ($entity->get('schema_poi') as $item) {
      $poi = $item->entity;
      if (!$poi) {
        continue;
      }
      $mentions[] = [
        '@type' => 'TouristAttraction',
        '@id' => $poi->toUrl('canonical', ['absolute' => TRUE])->toString() . '#poi',
        'name' => $poi->label(),
      ];
    }
    return $mentions;
  }

  protected static function buildSpatialCoverage(EntityInterface $entity): ?array {
    // Implemented in Step 0g-iv.
    return NULL;
  }

}
