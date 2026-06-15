<?php

namespace Drupal\seanmontague_map\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Builds map markers and base map config from a node.
 *
 * Generalized to serve two callers from one marker-collection core:
 *   - build()      → ARTICLE nodes. Markers from schema_destination / schema_poi
 *                    / schema_lodging, decorated with a `type` string
 *                    ('destination'/'poi') + a bold label. map_zoom is constant.
 *   - buildPage()  → PAGE nodes (front page + reusable landing pages). Markers
 *                    from a single field_map_markers (mixed geo_entity refs),
 *                    decorated with a bundle-based `color` hex + a bold label.
 *                    map_center from schema_geo; map_zoom from field_map_zoom.
 *
 * Both share collectMarkers(), which does the field loop + geo guard + the
 * verified DIRECT geofield getter ($entity->get('schema_geo')->lat / ->lon, NO
 * ->value). The per-marker shape is supplied by a DECORATOR callback so the two
 * callers' differing marker attributes (type/label vs color) live in their own
 * build methods — the shared core never changes behavior between them.
 *
 * Returns NEUTRAL data arrays; the preprocess hook maps them to template vars
 * and computes the unified has_map.
 */
class ArticleMapData {

  /**
   * Default article map zoom (pre-fitBounds; overridden when geometry present).
   */
  const DEFAULT_MAP_ZOOM = 13;

  /**
   * Default page map zoom when field_map_zoom is empty.
   */
  const DEFAULT_PAGE_ZOOM = 11;

  /**
   * Marker colors (page markers use explicit hex; matches the front template).
   */
  const COLOR_LODGING = '#a05a00';
  const COLOR_DEFAULT = '#3a5a40';

  /**
   * Build marker + map-config data for an ARTICLE node.
   *
   * Behavior is identical to the original (pre-generalization) service: three
   * geo-entity fields, `type` + bold label per marker, constant zoom.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The article node.
   *
   * @return array
   *   Keyed: map_markers, map_zoom, tiles.
   */
  public function build(NodeInterface $node): array {
    // Decorator: article markers carry a `type` string + a bold label.
    $decorate = static function (EntityInterface $entity, string $type): array {
      return [
        'type'  => $type,
        'label' => '<strong>' . $entity->label() . '</strong>',
      ];
    };

    $map_markers = [];
    $this->collectArticleMarkers($node, $map_markers, $decorate);

    $tiles = $node->hasField('field_map_tiles') ? $node->get('field_map_tiles')->value : NULL;

    return [
      'map_markers' => $map_markers,
      'map_zoom'    => self::DEFAULT_MAP_ZOOM,
      'tiles'       => $tiles,
    ];
  }

  /**
   * Build marker + map-config data for a TRIP node.
   *
   * Aggregates markers across all itinerary articles.
   *
   * @param \Drupal\node\NodeInterface $trip
   *   The tourist_trip node.
   *
   * @return array
   *   Keyed: map_markers, map_zoom, tiles.
   */
  public function buildTrip(NodeInterface $trip): array {
    $decorate = static function (EntityInterface $entity, string $type): array {
      return [
        'type'  => $type,
        'label' => '<strong>' . $entity->label() . '</strong>',
      ];
    };

    $map_markers = [];
    if ($trip->hasField('schema_itinerary')) {
      foreach ($trip->get('schema_itinerary') as $item) {
        $article = $item->entity;
        if ($article instanceof NodeInterface) {
          $this->collectArticleMarkers($article, $map_markers, $decorate);
        }
      }
    }

    $tiles = $trip->hasField('field_map_tiles') ? $trip->get('field_map_tiles')->value : NULL;

    return [
      'map_markers' => $map_markers,
      'map_zoom'    => self::DEFAULT_MAP_ZOOM,
      'tiles'       => $tiles,
    ];
  }

  /**
   * Internal helper to collect standard article markers.
   */
  protected function collectArticleMarkers(NodeInterface $node, array &$map_markers, callable $decorate): void {
    // Destination markers — type 'destination' (sky blue, styled client-side).
    $this->collectMarkers($node, 'schema_destination', $map_markers, fn(EntityInterface $e) => $decorate($e, 'destination'));
    // POI markers — type 'poi' (forest green).
    $this->collectMarkers($node, 'schema_poi', $map_markers, fn(EntityInterface $e) => $decorate($e, 'poi'));
    // Lodging markers — rendered as 'destination' type (matching the template).
    $this->collectMarkers($node, 'schema_lodging', $map_markers, fn(EntityInterface $e) => $decorate($e, 'destination'));
  }

  /**
   * Build marker + map-config data for a PAGE node (front + landing pages).
   *
   * Markers from a single field_map_markers (mixed geo_entity references),
   * decorated with a bundle-based `color` hex (lodging → brown, else green) plus
   * a bold label, matching the front-page template. map_center from schema_geo;
   * map_zoom from field_map_zoom (default 11).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The page node.
   *
   * @return array
   *   Keyed: map_markers, map_center, map_zoom, has_geo.
   */
  public function buildPage(NodeInterface $node): array {
    // Decorator: page markers carry a bundle-based color hex + a bold label
    // (matches the front template's '<strong>' ~ geo_entity.label.value popup/
    // tooltip; for these geo_entity refs, label() == the label field value).
    $decorate = static function (EntityInterface $entity): array {
      return [
        'color' => $entity->bundle() === 'lodging' ? self::COLOR_LODGING : self::COLOR_DEFAULT,
        'label' => '<strong>' . $entity->label() . '</strong>',
      ];
    };

    $map_markers = [];
    $this->collectMarkers($node, 'field_map_markers', $map_markers, $decorate);

    // Map center from the node's own schema_geo geofield (direct getter).
    $has_geo = $node->hasField('schema_geo') && !$node->get('schema_geo')->isEmpty();
    if ($has_geo) {
      $geo = $node->get('schema_geo');
      $map_center = $geo->lat . ',' . $geo->lon;
    }
    else {
      $map_center = NULL;
    }

    $map_zoom = self::DEFAULT_PAGE_ZOOM;
    if ($node->hasField('field_map_zoom') && $node->get('field_map_zoom')->value !== NULL && $node->get('field_map_zoom')->value !== '') {
      $map_zoom = $node->get('field_map_zoom')->value;
    }

    return [
      'map_markers' => $map_markers,
      'map_center'  => $map_center,
      'map_zoom'    => $map_zoom,
      'has_geo'     => $has_geo,
    ];
  }

  /**
   * Append markers for a referenced geo-entity field to the markers array.
   *
   * Shared core for both callers. Iterates the field, skips entities without a
   * non-empty schema_geo, reads lat/lon via the verified DIRECT getter, and
   * merges the caller-supplied decorator attributes (type/label OR color) onto
   * each marker.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $field
   *   The entity-reference field name.
   * @param array $map_markers
   *   The markers array, appended to by reference.
   * @param callable $decorate
   *   fn(EntityInterface $entity): array — returns the per-marker attributes to
   *   merge with lat/lon (e.g. ['type'=>…, 'label'=>…] or ['color'=>…]).
   */
  protected function collectMarkers(NodeInterface $node, string $field, array &$map_markers, callable $decorate): void {
    if (!$node->hasField($field)) {
      return;
    }
    foreach ($node->get($field) as $item) {
      $entity = $item->entity;
      if (!$entity || !$entity->hasField('schema_geo') || $entity->get('schema_geo')->isEmpty()) {
        continue;
      }
      $geo = $entity->get('schema_geo');
      $map_markers[] = [
        'lat' => $geo->lat,
        'lon' => $geo->lon,
      ] + $decorate($entity);
    }
  }

}
