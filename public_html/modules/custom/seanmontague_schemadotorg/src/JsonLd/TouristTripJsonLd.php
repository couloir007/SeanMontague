<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\node\NodeInterface;

/**
 * Builds TouristTrip JSON-LD additions for tourist_trip nodes.
 *
 * Maps field_trip_dates (Smart Date) to Schema.org departureTime /
 * arrivalTime. Both properties are derived from the single Smart Date
 * field rather than two separate date fields, so this hook handles the
 * mapping instead of Blueprints UI.
 */
class TouristTripJsonLd {

  public static function alter(array &$data, NodeInterface $entity): void {
    self::buildDates($data, $entity);
  }

  protected static function buildDates(array &$data, NodeInterface $entity): void {
    if ($entity->get('field_trip_dates')->isEmpty()) {
      return;
    }
    $item = $entity->get('field_trip_dates')->first();
    $start = $item->get('value')->getValue();
    $end   = $item->get('end_value')->getValue();

    if ($start) {
      // Smart Date stores Unix timestamps — format as ISO 8601 date.
      $data['departureTime'] = date('Y-m-d', $start);
    }
    if ($end) {
      $data['arrivalTime'] = date('Y-m-d', $end);
    }
  }

}
