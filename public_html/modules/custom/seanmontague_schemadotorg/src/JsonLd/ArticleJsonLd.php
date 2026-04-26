<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
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

    $spatial = static::buildContentLocation($entity);
    if ($spatial) {
      $data['contentLocation'] = $spatial;
    }
  }

  protected static function buildMentions(EntityInterface $entity): array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_poi') || $entity->get('schema_poi')->isEmpty()) {
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

  protected static function buildContentLocation(EntityInterface $entity): ?array {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_geoshape') || $entity->get('schema_geoshape')->isEmpty()) {
      return NULL;
    }

    $media = $entity->schema_geoshape->entity;
    if (!$media) {
      return NULL;
    }

    $file = $media->field_media_file->entity ?? NULL;
    if (!$file) {
      return NULL;
    }

    $path = \Drupal::service('file_system')->realpath($file->getFileUri());
    if (!$path || !file_exists($path)) {
      return NULL;
    }

    $content = @file_get_contents($path);
    if (!$content) {
      return NULL;
    }

    try {
      $geojson = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      return NULL;
    }

    $coords = static::extractLineCoords($geojson);
    if (empty($coords)) {
      return NULL;
    }

    $coords = static::subsample($coords, 50);

    // Schema.org GeoShape line uses "lat,lon" pairs separated by spaces.
    // GeoJSON coordinates are [lon, lat, ?ele] — swap indices.
    $pairs = array_map(
      fn($c) => round($c[1], 5) . ',' . round($c[0], 5),
      $coords,
    );

    return [
      '@type' => 'Place',
      'geo'   => [
        '@type' => 'GeoShape',
        'line'  => implode(' ', $pairs),
      ],
    ];
  }

  /**
   * Returns coordinate array from the first usable LineString in a GeoJSON
   * FeatureCollection. Handles LineString and MultiLineString geometries.
   *
   * @return array
   *   Array of coordinate arrays [lon, lat] or [lon, lat, ele], or empty.
   */
  private static function extractLineCoords(array $geojson): array {
    if (($geojson['type'] ?? '') !== 'FeatureCollection') {
      return [];
    }

    foreach ($geojson['features'] ?? [] as $feature) {
      $geom = $feature['geometry'] ?? NULL;
      if (!$geom) {
        continue;
      }

      $type = $geom['type'] ?? '';
      $coordinates = $geom['coordinates'] ?? [];

      if ($type === 'LineString' && !empty($coordinates)) {
        return $coordinates;
      }

      if ($type === 'MultiLineString' && !empty($coordinates[0])) {
        return $coordinates[0];
      }
    }

    return [];
  }

  /**
   * Subsamples a coordinate array to at most $max points, distributed evenly.
   *
   * Always includes the first and last points. When $count <= $max, returns
   * the array unchanged.
   *
   * @param array $coords
   *   Input coordinate array.
   * @param int $max
   *   Maximum number of output points.
   *
   * @return array
   */
  private static function subsample(array $coords, int $max): array {
    $count = count($coords);
    if ($count <= $max) {
      return $coords;
    }

    $result = [];
    for ($i = 0; $i < $max; $i++) {
      $index = (int) round($i * ($count - 1) / ($max - 1));
      $result[] = $coords[$index];
    }
    return $result;
  }

}
