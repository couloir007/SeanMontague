<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;

/**
 * Enriches the TouristTrip mainEntity JSON-LD.
 *
 * Runs on the TouristTrip pass only (see the entity alter hook). Adds the trip's
 * departure/arrival times from the Smart Date field and an ordered itinerary of
 * the destination Places, each nesting its points of interest as containsPlace
 * (TouristAttraction). Does NOT set copyrightYear — TouristTrip extends Trip,
 * not CreativeWork, so copyrightYear is invalid here (it belongs on the WebPage
 * wrapper, set in the page-level alter hook).
 */
class TouristTripJsonLd {

  public static function alter(array &$data, NodeInterface|EntityInterface $entity, BubbleableMetadata $bubbleable_metadata): void {

    // ── Trip times from the Smart Date field ──
    // A trip DEPARTS at its start and ARRIVES at its end.
    if ($entity->hasField('schema_trip_dates') && !$entity->get('schema_trip_dates')->isEmpty()) {
      $values    = $entity->get('schema_trip_dates')->first()->getValue();
      $departure = !empty($values['value'])     ? date('Y-m-d', (int) $values['value'])     : NULL;
      $arrival   = !empty($values['end_value']) ? date('Y-m-d', (int) $values['end_value']) : NULL;
      if ($departure) { $data['departureTime'] = $departure; }
      if ($arrival)   { $data['arrivalTime']   = $arrival; }
    }

    // ── itinerary — ordered destination Places, each nesting its POIs ──
    if ($entity->hasField('schema_itinerary') && !$entity->get('schema_itinerary')->isEmpty()) {
      $itinerary = static::buildItinerary($entity);
      if ($itinerary) {
        $data['itinerary'] = $itinerary;
      }
    }

  }

  /**
   * Builds the itinerary ItemList from the trip's day articles.
   *
   * Walks schema_itinerary (day articles) in order, collecting each day's
   * destinations (schema_destination → tourist_destination) and POIs
   * (schema_poi → geo_entity:poi), deduped across the whole trip. POIs are
   * nested under the destination they reference (the POI's schema_place) via
   * containsPlace; POIs that don't reference a trip destination are appended as
   * their own itinerary stops so none are lost.
   *
   * @return array|null
   *   An ItemList array, or NULL when there are no destinations or POIs.
   */
  protected static function buildItinerary(NodeInterface $entity): ?array {
    /** @var array<int,array{place:array,pois:array}> $destinations */
    $destinations = [];
    $dest_order   = [];
    /** @var array<int,array{attraction:array,place_id:?int}> $pois */
    $pois = [];

    foreach ($entity->get('schema_itinerary') as $ref) {
      $article = $ref->entity;
      if (!$article instanceof NodeInterface) {
        continue;
      }

      if ($article->hasField('schema_destination')) {
        foreach ($article->get('schema_destination') as $dref) {
          $dest = $dref->entity;
          if ($dest instanceof NodeInterface && !isset($destinations[$dest->id()])) {
            $destinations[$dest->id()] = ['place' => static::placeData($dest), 'pois' => []];
            $dest_order[] = $dest->id();
          }
        }
      }

      if ($article->hasField('schema_poi')) {
        foreach ($article->get('schema_poi') as $pref) {
          $poi = $pref->entity;
          if ($poi instanceof ContentEntityInterface && !isset($pois[$poi->id()])) {
            $place_id = NULL;
            if ($poi->hasField('schema_place') && !$poi->get('schema_place')->isEmpty() && $poi->get('schema_place')->entity) {
              $place_id = (int) $poi->get('schema_place')->entity->id();
            }
            $pois[$poi->id()] = ['attraction' => static::attractionData($poi), 'place_id' => $place_id];
          }
        }
      }
    }

    // Attach each POI to its destination, or hold it as an orphan stop.
    $orphans = [];
    foreach ($pois as $poi) {
      if ($poi['place_id'] !== NULL && isset($destinations[$poi['place_id']])) {
        $destinations[$poi['place_id']]['pois'][] = $poi['attraction'];
      }
      else {
        $orphans[] = $poi['attraction'];
      }
    }

    // Ordered destinations first, then any orphan POIs.
    $elements = [];
    $position = 0;
    foreach ($dest_order as $id) {
      $place = $destinations[$id]['place'];
      if (!empty($destinations[$id]['pois'])) {
        $place['containsPlace'] = $destinations[$id]['pois'];
      }
      $elements[] = ['@type' => 'ListItem', 'position' => ++$position, 'item' => $place];
    }
    foreach ($orphans as $attraction) {
      $elements[] = ['@type' => 'ListItem', 'position' => ++$position, 'item' => $attraction];
    }

    return $elements ? ['@type' => 'ItemList', 'itemListElement' => $elements] : NULL;
  }

  /**
   * Builds a Place node's JSON-LD (name + geo).
   */
  protected static function placeData(NodeInterface $dest): array {
    $place = ['@type' => 'Place', 'name' => $dest->label()];
    if ($dest->hasField('schema_geo') && !$dest->get('schema_geo')->isEmpty()) {
      $geo = $dest->get('schema_geo')->first();
      if ($geo->lat !== NULL && $geo->lon !== NULL) {
        $place['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $geo->lat, 'longitude' => (float) $geo->lon];
      }
    }
    return $place;
  }

  /**
   * Builds a POI geo_entity's JSON-LD (TouristAttraction, name + geo).
   */
  protected static function attractionData(ContentEntityInterface $poi): array {
    $attraction = ['@type' => 'TouristAttraction', 'name' => $poi->label()];
    if ($poi->hasField('schema_geo') && !$poi->get('schema_geo')->isEmpty()) {
      $geo = $poi->get('schema_geo')->first();
      if ($geo->lat !== NULL && $geo->lon !== NULL) {
        $attraction['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $geo->lat, 'longitude' => (float) $geo->lon];
      }
    }
    return $attraction;
  }

}
