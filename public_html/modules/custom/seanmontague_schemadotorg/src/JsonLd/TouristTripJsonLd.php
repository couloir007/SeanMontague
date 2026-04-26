<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;

class TouristTripJsonLd {

  public static function alter(array &$data, NodeInterface|EntityInterface $entity, BubbleableMetadata $bubbleable_metadata): void {

    // Trip dates from Smart Date field.
    if ($entity->hasField('schema_trip_dates') && !$entity->get('schema_trip_dates')->isEmpty()) {
      $item     = $entity->get('schema_trip_dates')->first();
      $values   = $item->getValue();
      $arrived  = !empty($values['value'])     ? date('Y-m-d', (int) $values['value'])     : NULL;
      $departed = !empty($values['end_value']) ? date('Y-m-d', (int) $values['end_value']) : NULL;
      if ($arrived)  { $data['arrivalTime']   = $arrived; }
      if ($departed) { $data['departureTime'] = $departed; }
    }

    // copyrightYear from schema_date_published or node created time.
    $year = NULL;
    if ($entity->hasField('schema_date_published') && !$entity->get('schema_date_published')->isEmpty()) {
      $ts = strtotime($entity->get('schema_date_published')->value);
      if ($ts !== FALSE) {
        $year = (int) date('Y', (int) $ts);
      }
    }
    if (!$year) {
      $year = (int) date('Y', (int) $entity->getCreatedTime());
    }
    $data['copyrightYear'] = $year;

  }
}
