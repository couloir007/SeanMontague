<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;

class TouristTripJsonLd {

  public static function alter(array &$data, NodeInterface $entity): void {
    self::buildDates($data, $entity);
    self::buildCopyrightYear($data, $entity);
  }

  protected static function buildDates(array &$data, NodeInterface $entity): void {
//    if (!$entity instanceof ContentEntityInterface
//      || !$entity->hasField('schema_trip_dates')
//      || $entity->get('schema_trip_dates')->isEmpty()) {
//      return;
//    }
//
//    ray($data);
//
//    $item = $entity->get('schema_trip_dates')->first();
//    $values = $item->getValue();
//
//    $arrived  = !empty($values['value'])     ? date('Y-m-d', (int) $values['value'])     : NULL;
//    $departed = !empty($values['end_value']) ? date('Y-m-d', (int) $values['end_value']) : NULL;
//
//    if ($arrived)  { $data['mainEntity']['arrivalTime']   = $arrived; }
//    if ($departed) { $data['mainEntity']['departureTime'] = $departed; }
  }

  protected static function buildCopyrightYear(array &$data, NodeInterface $entity): void {
//    ray($data);
//    if (!$entity instanceof ContentEntityInterface
//      || !$entity->hasField('schema_date_published')
//      || $entity->get('schema_date_published')->isEmpty()) {
//      $data['mainEntity']['copyrightYear'] = (int) date('Y', $entity->getCreatedTime());
//      return;
//    }
//    $value = $entity->get('schema_date_published')->value;
//    $ts    = $value ? strtotime($value) : FALSE;
//    $data['mainEntity']['copyrightYear'] = $ts !== FALSE
//      ? (int) date('Y', $ts)
//      : (int) date('Y', $entity->getCreatedTime());
  }

}
