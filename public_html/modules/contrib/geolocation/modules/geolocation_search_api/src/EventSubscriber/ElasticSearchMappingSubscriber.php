<?php

namespace Drupal\geolocation_search_api\EventSubscriber;

use Drupal\elasticsearch_connector\Event\FieldMappingEvent;
use Drupal\elasticsearch_connector\Event\SupportsDataTypeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Makes the Geolocation data types usable on Elasticsearch indexes.
 *
 * The backend asks through events which data types it supports and how to map
 * them. Types it is not told about are indexed as plain keywords.
 *
 * @see \Drupal\elasticsearch_connector\SearchAPI\FieldMapper::mapFieldProperty()
 */
class ElasticSearchMappingSubscriber implements EventSubscriberInterface {

  /**
   * Elasticsearch field type, keyed by Geolocation Search API data type.
   */
  protected const FIELD_TYPES = [
    'geolocation_coordinates' => 'geo_point',
    'geolocation_geometry' => 'geo_shape',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SupportsDataTypeEvent::class => 'onSupportsDataType',
      FieldMappingEvent::class => 'onFieldMapping',
    ];
  }

  /**
   * Declares the Geolocation data types supported by the backend.
   *
   * @param \Drupal\elasticsearch_connector\Event\SupportsDataTypeEvent $event
   *   Data type support event.
   */
  public function onSupportsDataType(SupportsDataTypeEvent $event): void {
    if (array_key_exists($event->getType(), self::FIELD_TYPES)) {
      $event->setIsSupported(TRUE);
    }
  }

  /**
   * Maps a Geolocation field onto its Elasticsearch geo type.
   *
   * The values need no conversion, as Elasticsearch parses both the "lat,lng"
   * string and WKT natively.
   *
   * @param \Drupal\elasticsearch_connector\Event\FieldMappingEvent $event
   *   Field mapping event.
   */
  public function onFieldMapping(FieldMappingEvent $event): void {
    $field_type = self::FIELD_TYPES[$event->getField()->getType()] ?? NULL;

    if ($field_type) {
      $event->setParam(['type' => $field_type]);
    }
  }

}
