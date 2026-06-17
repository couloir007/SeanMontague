<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;

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




  }
}
