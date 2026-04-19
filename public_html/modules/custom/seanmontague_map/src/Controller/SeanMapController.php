<?php

namespace Drupal\seanmontague_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Aggregates all four geo data sources into a single GeoJSON FeatureCollection.
 *
 * Sources:
 *   place nodes        → Point markers (schema_latitude / schema_longitude)
 *   geo_entity:poi     → Point markers (schema_geo Geofield, field_show_on_map)
 *   article nodes      → Point markers (schema_geo first, geoshape centroid fallback)
 *   tourist_trip nodes → LineString polylines (schema_destination → place coords)
 */
class SeanMapController extends ControllerBase {

  /**
   * Returns a GeoJSON FeatureCollection of all map-eligible entities.
   */
  public function items(): JsonResponse {
    $features = array_merge(
      $this->collectPlaces(),
      $this->collectPois(),
      $this->collectArticles(),
      $this->collectTrips(),
    );

    return new JsonResponse([
      'type'     => 'FeatureCollection',
      'features' => $features,
    ]);
  }

  // ---------------------------------------------------------------------------
  // Collectors
  // ---------------------------------------------------------------------------

  protected function collectPlaces(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'place')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $features = [];
    foreach ($storage->loadMultiple($ids) as $place) {
      $lat = (float) ($place->schema_latitude->value ?? 0);
      $lon = (float) ($place->schema_longitude->value ?? 0);
      if (!$lat && !$lon) {
        continue;
      }

      $props = $this->buildBaseProperties($place, 'place');

      if (!$place->get('schema_address')->isEmpty()) {
        $addr = $place->schema_address->first();
        $props['address'] = implode(', ', array_filter([
          $addr->locality,
          $addr->administrative_area,
          $addr->country_code,
        ]));
      }

      $props['popup_html'] = $this->renderPopup('place', $props);

      $features[] = $this->makePointFeature($place, $props, $lat, $lon);
    }

    return $features;
  }

  protected function collectPois(): array {
    $storage = $this->entityTypeManager()->getStorage('geo_entity');
    $ids = $storage->getQuery()
      ->condition('bundle', 'poi')
      ->condition('field_show_on_map', 1)
      ->accessCheck(FALSE)
      ->execute();

    $features = [];
    foreach ($storage->loadMultiple($ids) as $poi) {
      if ($poi->get('schema_geo')->isEmpty()) {
        continue;
      }
      $item = $poi->get('schema_geo')->first();
      $lat  = (float) $item->get('lat')->getValue();
      $lon  = (float) $item->get('lon')->getValue();
      if (!$lat && !$lon) {
        continue;
      }

      $props = $this->buildBaseProperties($poi, 'poi');

      if (!$poi->get('field_body')->isEmpty()) {
        $text = strip_tags((string) $poi->field_body->value);
        $props['teaser'] = mb_substr($text, 0, 120) . (mb_strlen($text) > 120 ? '…' : '');
      }

      $props['popup_html'] = $this->renderPopup('poi', $props);

      $features[] = $this->makePointFeature($poi, $props, $lat, $lon);
    }

    return $features;
  }

  protected function collectArticles(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $features = [];
    foreach ($storage->loadMultiple($ids) as $article) {
      $point = $this->resolveArticlePoint($article);
      if (!$point) {
        continue;
      }

      $props = $this->buildBaseProperties($article, 'article');

      $cat = $article->schema_category->entity ?? NULL;
      $props['category'] = $cat ? $cat->label() : '';

      $actType = $article->schema_activity_type->entity ?? NULL;
      $props['activity_type'] = $actType ? $actType->label() : '';

      if (!$article->get('schema_distance')->isEmpty()) {
        $props['distance'] = $article->schema_distance->value . ' mi';
      }

      if (!$article->get('schema_date_published')->isEmpty()) {
        $ts = strtotime((string) $article->schema_date_published->value);
        $props['date'] = $ts ? date('M j, Y', $ts) : '';
      }

      $props['popup_html'] = $this->renderPopup('article', $props);

      $features[] = $this->makePointFeature($article, $props, $point['lat'], $point['lon']);
    }

    return $features;
  }

  protected function collectTrips(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'tourist_trip')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $features = [];
    foreach ($storage->loadMultiple($ids) as $trip) {
      if ($trip->get('schema_destination')->isEmpty()) {
        continue;
      }

      $coords = [];
      foreach ($trip->schema_destination->referencedEntities() as $place) {
        $lat = (float) ($place->schema_latitude->value ?? 0);
        $lon = (float) ($place->schema_longitude->value ?? 0);
        if ($lat || $lon) {
          $coords[] = [$lon, $lat]; // GeoJSON order: [lon, lat]
        }
      }

      if (empty($coords)) {
        continue;
      }

      $props = $this->buildBaseProperties($trip, 'trip');
      $props['places_count'] = count($coords);

      if (!$trip->get('field_trip_dates')->isEmpty()) {
        $props['date_display'] = $this->formatTripDates($trip->field_trip_dates->first());
      }

      $props['popup_html'] = $this->renderPopup('trip', $props);

      $geometry = count($coords) === 1
        ? ['type' => 'Point',      'coordinates' => $coords[0]]
        : ['type' => 'LineString', 'coordinates' => $coords];

      $features[] = [
        'type'       => 'Feature',
        'id'         => 'trip-' . $trip->id(),
        'geometry'   => $geometry,
        'properties' => $props,
      ];
    }

    return $features;
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  protected function makePointFeature($entity, array $props, float $lat, float $lon): array {
    $props['lat'] = $lat;
    $props['lon'] = $lon;

    return [
      'type'       => 'Feature',
      'id'         => $props['type'] . '-' . $entity->id(),
      'geometry'   => ['type' => 'Point', 'coordinates' => [$lon, $lat]],
      'properties' => $props,
    ];
  }

  protected function buildBaseProperties($entity, string $type): array {
    return [
      'type'      => $type,
      'title'     => $entity->label(),
      'node_url'  => $entity->toUrl('canonical', ['absolute' => TRUE])->toString(),
      'image_url' => $this->resolveImageUrl($entity, $type),
      'color'     => $this->colorForType($type),
    ];
  }

  /**
   * Resolves a thumbnail URL from schema_image or field_image media.
   *
   * Uses the 'medium' image style when available.
   */
  protected function resolveImageUrl($entity, string $type): ?string {
    $field = ($type === 'article') ? 'field_image' : 'schema_image';

    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return NULL;
    }
    $media = $entity->get($field)->entity;
    if (!$media) {
      return NULL;
    }

    try {
      $source      = $media->getSource();
      $sourceField = $source->getSourceFieldDefinition($media->bundle->entity)->getName();
      if ($media->get($sourceField)->isEmpty()) {
        return NULL;
      }
      $file = $media->get($sourceField)->entity;
      if (!$file) {
        return NULL;
      }

      $style = $this->entityTypeManager()->getStorage('image_style')->load('medium');
      return $style
        ? $style->buildUrl($file->getFileUri())
        : $file->createFileUrl(FALSE);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Returns lat/lon for an article: schema_geo first, then first geoshape coord.
   *
   * @return array{lat: float, lon: float}|null
   */
  protected function resolveArticlePoint($article): ?array {
    if (!$article->get('schema_geo')->isEmpty()) {
      $item = $article->get('schema_geo')->first();
      $lat  = (float) $item->get('lat')->getValue();
      $lon  = (float) $item->get('lon')->getValue();
      if ($lat || $lon) {
        return ['lat' => $lat, 'lon' => $lon];
      }
    }

    if (!$article->get('schema_geoshape')->isEmpty()) {
      $media = $article->schema_geoshape->entity;
      $file  = $media ? ($media->field_media_file->entity ?? NULL) : NULL;
      if ($file) {
        $path = \Drupal::service('file_system')->realpath($file->getFileUri());
        if ($path && file_exists($path)) {
          try {
            $geojson = json_decode(@file_get_contents($path), TRUE, 512, JSON_THROW_ON_ERROR);
            foreach ($geojson['features'] ?? [] as $feature) {
              $coords = $feature['geometry']['coordinates'] ?? [];
              if (!empty($coords) && is_array($coords[0])) {
                return ['lat' => (float) $coords[0][1], 'lon' => (float) $coords[0][0]];
              }
            }
          }
          catch (\JsonException) {}
        }
      }
    }

    return NULL;
  }

  /**
   * Renders a popup Twig template for a feature type.
   *
   * Uses renderPlain() so assets are not bubbled into the JSON response.
   */
  protected function renderPopup(string $type, array $props): string {
    $build = [
      '#theme' => 'seanmontague_map_popup_' . $type,
      '#props' => $props,
    ];
    try {
      return (string) \Drupal::service('renderer')->renderPlain($build);
    }
    catch (\Exception $e) {
      return '<strong>' . htmlspecialchars($props['title'] ?? '', ENT_QUOTES) . '</strong>';
    }
  }

  /**
   * Formats Smart Date trip dates as "Jan 1–15, 2025" or "Jan 1, 2025".
   */
  protected function formatTripDates($item): string {
    $start = $item->get('value')->getValue();
    $end   = $item->get('end_value')->getValue();
    if ($start && $end) {
      return date('M j', $start) . '–' . date('M j, Y', $end);
    }
    return $start ? date('M j, Y', $start) : '';
  }

  protected function colorForType(string $type): string {
    return match ($type) {
      'place'   => '#5b8dee',
      'poi'     => '#3a5a40',
      'article' => '#e07b39',
      'trip'    => '#9055a2',
      default   => '#888888',
    };
  }

}
