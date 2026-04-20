<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;

/**
 * Builds TouristTrip JSON-LD additions for tourist_trip nodes.
 *
 * Maps schema_trip_dates (Smart Date) to Schema.org departureTime /
 * arrivalTime. Both properties are derived from the single Smart Date
 * field rather than two separate date fields, so this hook handles the
 * mapping instead of Blueprints UI.
 */
class TouristTripJsonLd {

  public static function alter(array &$data, NodeInterface $entity): void {
    self::buildDates($data, $entity);
  }

  protected static function buildDates(array &$data, NodeInterface $entity): void {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('schema_trip_dates') || $entity->get('schema_trip_dates')->isEmpty()) {
      return;
    }
    $items = $entity->get('schema_trip_dates');
    $item0 = $items->get(0);
    $item1 = $items->get(1);

    // schema_trip_dates cardinality=2:
    //   item 0 = date arrived at destination (arrivalTime)
    //   item 1 = date departed destination   (departureTime)
    if ($item0) {
      $arrived = $item0->get('value')->getValue();
      if ($arrived) {
        $data['arrivalTime'] = date('Y-m-d', $arrived);
      }
    }
    if ($item1) {
      $departed = $item1->get('value')->getValue();
      if ($departed) {
        $data['departureTime'] = date('Y-m-d', $departed);
      }
    }
  }

}
