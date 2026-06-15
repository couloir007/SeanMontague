<?php

namespace Drupal\trail_mapper\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\node\NodeInterface;

/**
 * Shapes an article node's track / geo / stats data for the map section.
 *
 * Step 1 of moving the data-shaping logic out of node--article.html.twig's
 * {% set %} blocks into testable PHP. This service is a faithful 1:1 port of
 * that Twig — it must compute IDENTICAL values. The template is unchanged in
 * this step; the preprocess hook will adopt this service in a later step.
 *
 * Two deliberate omissions, both supplied elsewhere:
 * - map_markers: destination / POI / lodging markers come from a separate
 *   service (Step 2), because a marker-only article must also get a map.
 * - has_map: the hook combines has_geoshape || has_geo || has_place ||
 *   has_markers. This service returns only the three booleans it can know.
 *
 * Track stats and elevation values are kept in METERS (raw field values); the
 * map / elevation JS converts to display units client-side.
 */
class ArticleGeoData {

  /**
   * Default map center — Kingdom Trails / Burke (matches the Twig fallback).
   */
  protected const DEFAULT_CENTER = '44.593,-71.918';

  /**
   * Distance-mode labels for the summary stats bar, in render order.
   *
   * Keyed by route_type field_key, matching the Twig mode_labels hash.
   */
  protected const MODE_LABELS = [
    'driving' => 'Driving',
    'ferry'   => 'Ferry',
    'cycling' => 'Cycling',
    'hiking'  => 'Hiking',
    'walking' => 'Walking',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Builds the track / geo / stats data array for an article node.
   *
   * Keys mirror the Twig variable names so the preprocess hook can assign them
   * directly.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The article node.
   *
   * @return array
   *   Associative array with: geojson_urls, geojson_url, track_stats,
   *   dist_modes, is_gpx, has_geoshape, has_geo, has_place, category_name,
   *   cat_key, stats, map_center.
   */
  public function build(NodeInterface $node): array {
    // ── Geo sources ──
    // schema_geoshape is multi-value: collect a URL for every referenced media
    // that has a file. The first file drives is_gpx and the elevation profile;
    // the full list drives the multi-track map.
    $geojson_urls = [];
    $track_stats  = [];
    $dist_modes   = [];

    $this->collectTracks($node, $geojson_urls, $track_stats, $dist_modes);

    $geo_file     = NULL;
    if ($node->hasField('schema_geoshape') && !$node->get('schema_geoshape')->isEmpty()) {
      $media = $node->get('schema_geoshape')->first()->entity;
      $geo_file = $media ? $media->get('field_media_file')->entity : NULL;
    }

    $geojson_url  = $geojson_urls[0] ?? NULL;
    $is_gpx       = $geo_file && preg_match('/\.gpx$/i', (string) $geo_file->getFilename()) === 1;
    $has_geoshape = !empty($geojson_urls);
    $has_geo      = !$node->get('schema_geo')->isEmpty();
    $place        = $node->get('schema_place')->entity;
    $has_place    = $place && $this->isNotEmpty($place->get('schema_latitude')->value);

    // ── Category ──
    $category = $node->get('schema_category')->entity;
    $cat_key  = ($category && $this->isNotEmpty($category->get('field_key')->value))
      ? $category->get('field_key')->value
      : NULL;
    // The term's display label. (The original template used
    // `category.label.value`, which always yielded '' — `.label` is the label()
    // method, and `.value` on its string result is empty. That latent bug is
    // fixed here by calling label() directly.)
    $category_name = $category ? (string) $category->label() : '';

    // ── Top summary stats bar — category-gated, computed from track media ──
    // Only 'trails' and 'travel' get a bar. Per-mode distances from dist_modes
    // (METERS). Distance stats carry `meters`; stats-bar.js converts live. No
    // elevation here (that lives only in the profile).
    $stats = [];
    if ($cat_key === 'trails' || $cat_key === 'travel') {
      foreach (self::MODE_LABELS as $key => $label) {
        if (isset($dist_modes[$key]) && $dist_modes[$key] > 0) {
          $stats[] = [
            'label'  => $label,
            'value'  => $dist_modes[$key],
            'unit'   => 'distance',
            'meters' => $dist_modes[$key],
          ];
        }
      }
      // Travel only — POI count (unit-agnostic, never converted).
      if ($cat_key === 'travel' && !$node->get('schema_poi')->isEmpty()) {
        $poi_count = $node->get('schema_poi')->count();
        if ($poi_count > 0) {
          $stats[] = [
            'label' => 'Points of Interest',
            'value' => $poi_count,
            'unit'  => NULL,
          ];
        }
      }
    }

    // ── Map center fallback — overridden by fitBounds when geojson present ──
    // Geofield uses the direct ->lat / ->lon getter (the verified site pattern,
    // no ->value); Place uses its decimal schema_latitude / schema_longitude.
    if ($has_geo) {
      $geo = $node->get('schema_geo');
      $map_center = $geo->lat . ',' . $geo->lon;
    }
    elseif ($has_place) {
      $map_center = $place->get('schema_latitude')->value . ',' . $place->get('schema_longitude')->value;
    }
    else {
      $map_center = self::DEFAULT_CENTER;
    }

    return [
      'geojson_urls'  => $geojson_urls,
      'geojson_url'   => $geojson_url,
      'track_stats'   => $track_stats,
      'dist_modes'    => $dist_modes,
      'is_gpx'        => $is_gpx,
      'has_geoshape'  => $has_geoshape,
      'has_geo'       => $has_geo,
      'has_place'     => $has_place,
      'category_name' => $category_name,
      'cat_key'       => $cat_key,
      'stats'         => $stats,
      'map_center'    => $map_center,
    ];
  }

  /**
   * Builds the track / geo / stats data array for a TRIP node.
   *
   * Aggregates tracks and distances across all itinerary articles.
   *
   * @param \Drupal\node\NodeInterface $trip
   *   The tourist_trip node.
   *
   * @return array
   *   Associative array with: geojson_urls, geojson_url, track_stats,
   *   dist_modes, distance_stats, has_geoshape.
   */
  public function buildTrip(NodeInterface $trip): array {
    $geojson_urls = [];
    $track_stats  = [];
    $dist_modes   = [];

    if ($trip->hasField('schema_itinerary')) {
      foreach ($trip->get('schema_itinerary') as $item) {
        $article = $item->entity;
        if ($article instanceof NodeInterface) {
          $this->collectTracks($article, $geojson_urls, $track_stats, $dist_modes);
        }
      }
    }

    $distance_stats = [];
    $multi = count($dist_modes) > 1;
    foreach (self::MODE_LABELS as $key => $label) {
      if (isset($dist_modes[$key]) && $dist_modes[$key] > 0) {
        $distance_stats[] = [
          'label'    => $multi ? 'Distance ' . $label : 'Distance',
          'value'    => $dist_modes[$key],
          'unit'     => 'distance',
          'meters'   => $dist_modes[$key],
          'is_small' => $multi,
        ];
      }
    }

    return [
      'geojson_urls'   => $geojson_urls,
      'geojson_url'    => $geojson_urls[0] ?? NULL,
      'track_stats'    => $track_stats,
      'dist_modes'     => $dist_modes,
      'distance_stats' => $distance_stats,
      'has_geoshape'   => !empty($geojson_urls),
    ];
  }

  /**
   * Appends tracks and distance sums from a node to the provided arrays.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to collect from.
   * @param array $geojson_urls
   *   The list of track URLs, appended to by reference.
   * @param array $track_stats
   *   The list of per-track stats, appended to by reference.
   * @param array $dist_modes
   *   The per-mode distance sums, updated by reference.
   */
  protected function collectTracks(NodeInterface $node, array &$geojson_urls, array &$track_stats, array &$dist_modes): void {
    if (!$node->hasField('schema_geoshape')) {
      return;
    }

    foreach ($node->get('schema_geoshape') as $item) {
      $media = $item->entity;
      $file  = $media ? $media->get('field_media_file')->entity : NULL;
      if (!$file) {
        continue;
      }

      $geojson_urls[] = $this->fileUrlGenerator->generateString($file->getFileUri());

      // Per-mode distance totals (METERS), summed by route_type key.
      $rt_key = $media->get('field_route_type')->entity?->get('field_key')->value;
      $distance = $media->get('field_distance')->value;
      if ($rt_key && $this->isNotEmpty($distance)) {
        $prev = $dist_modes[$rt_key] ?? 0;
        $dist_modes[$rt_key] = $prev + $distance;
      }

      // Index-matched to geojson_urls (same media per index). Values in METERS;
      // JS converts. NULL is fine — JS skips null stats.
      $track_stats[] = [
        'distance'      => $media->get('field_distance')->value,
        'ascent'        => $media->get('field_ascent')->value,
        'descent'       => $media->get('field_descent')->value,
        'min_elev'      => $media->get('field_min_elevation')->value,
        'max_elev'      => $media->get('field_max_elevation')->value,
        'duration'      => $media->get('field_duration')->value,
        'duration_unit' => $media->get('field_duration_units')->value,
        'route_type'    => $rt_key,
        'name'          => $media->get('name')->value,
      ];
    }
  }

  /**
   * Mirrors Twig's `is not empty` test for scalar field values.
   *
   * Twig treats '', null, false and [] as empty; notably 0 / '0' are NOT empty.
   * Field `->value` reads return string|null, so this matches the template.
   *
   * @param mixed $value
   *   The field value to test.
   *
   * @return bool
   *   TRUE when the value is not empty by Twig's rules.
   */
  protected function isNotEmpty($value): bool {
    return $value !== '' && $value !== NULL && $value !== FALSE && $value !== [];
  }

}
