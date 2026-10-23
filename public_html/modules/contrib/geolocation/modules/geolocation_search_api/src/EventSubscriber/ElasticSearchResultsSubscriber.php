<?php

namespace Drupal\geolocation_search_api\EventSubscriber;

use Drupal\search_api\Event\ProcessingResultsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds the distance calculated by Elasticsearch to the result items.
 *
 * Result items are built from "_source" alone, so the script fields holding the
 * distance have to be read off the raw response. Set on the item, they render
 * through the ordinary Search API Views field.
 *
 * @see \Drupal\geolocation_search_api\EventSubscriber\ElasticSearchQuerySubscriber::distanceScriptFields()
 * @see \Drupal\elasticsearch_connector\SearchAPI\Query\QueryResultParser::parseResult()
 */
class ElasticSearchResultsSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the subscriber.
   *
   * @param \Drupal\search_api\Utility\FieldsHelperInterface $fieldsHelper
   *   Search API fields helper.
   */
  public function __construct(
    protected FieldsHelperInterface $fieldsHelper,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::PROCESSING_RESULTS => 'onProcessingResults',
    ];
  }

  /**
   * Adds the distance pseudo fields to the result items.
   *
   * @param \Drupal\search_api\Event\ProcessingResultsEvent $event
   *   Processing results event.
   */
  public function onProcessingResults(ProcessingResultsEvent $event): void {
    $results = $event->getResults();

    // Only an Elasticsearch search sets this, so no backend check is needed.
    $response = $results->getExtraData('elasticsearch_response');
    if (!is_array($response) || empty($response['hits']['hits'])) {
      return;
    }

    $distances = [];
    foreach ($response['hits']['hits'] as $hit) {
      foreach ($hit['fields'] ?? [] as $name => $values) {
        if (!str_ends_with($name, '__distance')) {
          continue;
        }

        $values = array_values(array_filter($values, 'is_numeric'));
        if ($values) {
          $distances[$hit['_id']][$name] = array_map('floatval', $values);
        }
      }
    }

    if (!$distances) {
      return;
    }

    $index = $results->getQuery()->getIndex();
    foreach ($results->getResultItems() as $id => $item) {
      foreach ($distances[$id] ?? [] as $name => $values) {
        $field = $this->fieldsHelper->createField($index, $name, [
          'label' => $name,
          'property_path' => $name,
          'type' => 'decimal',
        ]);
        $field->setValues($values);
        $item->setField($name, $field);
      }
    }
  }

}
