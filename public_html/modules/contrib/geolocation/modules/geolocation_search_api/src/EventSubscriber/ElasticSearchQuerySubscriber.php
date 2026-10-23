<?php

namespace Drupal\geolocation_search_api\EventSubscriber;

use Drupal\elasticsearch_connector\Event\QueryParamsEvent;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Translates the Geolocation query options into Elasticsearch query DSL.
 *
 * The backend builds no geo queries itself, so the proximity and boundary
 * filters are applied here, on the otherwise finished request body.
 *
 * Elasticsearch 9 resolves geo_distance and geo_bounding_box against geo_shape
 * fields as well as geo_point ones, on Elasticsearch 8 only against geo_point.
 *
 * @see \Drupal\elasticsearch_connector\SearchAPI\Query\QueryParamBuilder::buildQueryParams()
 * @see \Drupal\geolocation_search_api\Plugin\views\filter\GeolocationSearchApiFilterProximity::query()
 * @see \Drupal\geolocation_search_api\Plugin\views\filter\GeolocationSearchApiFilterBoundary::query()
 */
class ElasticSearchQuerySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      QueryParamsEvent::class => 'onQueryParams',
    ];
  }

  /**
   * Adds geo filters and sorts to the Elasticsearch request.
   *
   * @param \Drupal\elasticsearch_connector\Event\QueryParamsEvent $event
   *   Query params event.
   */
  public function onQueryParams(QueryParamsEvent $event): void {
    $query = $event->getQuery();

    $filters = array_merge($this->proximityFilters($query), $this->boundaryFilters($query));
    $sorts = $this->distanceSorts($query);
    $script_fields = $this->distanceScriptFields($query);

    if (!$filters && !$sorts && !$script_fields) {
      return;
    }

    $params = $event->getParams();

    if ($script_fields) {
      $params['body']['script_fields'] = $script_fields + ($params['body']['script_fields'] ?? []);

      // Script fields make Elasticsearch omit "_source" unless it is asked for
      // explicitly, and result items are built from it.
      // @see \Drupal\elasticsearch_connector\SearchAPI\Query\QueryResultParser::parseResult()
      $params['body']['_source'] ??= TRUE;
    }

    if ($filters) {
      $params['body']['query'] = $this->withFilters($params['body']['query'] ?? NULL, $filters);
    }

    if ($sorts) {
      $params['body']['sort'] = array_merge($this->sortToList($params['body']['sort'] ?? []), $sorts);
    }

    $event->setParams($params);
  }

  /**
   * Builds geo_distance filters from the proximity option.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Search API query.
   *
   * @return array
   *   Elasticsearch filter clauses.
   */
  protected function proximityFilters(QueryInterface $query): array {
    $filters = [];

    foreach ($this->spatials($query) as $spatial) {
      // Entries without a radius only carry a center for the distance sort.
      if (!is_numeric($spatial['radius'] ?? NULL)) {
        continue;
      }

      $filters[] = [
        'geo_distance' => [
          // Proximity distances are always converted to kilometers.
          // @see \Drupal\geolocation\ProximityTrait::getDistanceConversions()
          'distance' => $spatial['radius'] . 'km',
          $spatial['field'] => [
            'lat' => (float) $spatial['lat'],
            'lon' => (float) $spatial['lon'],
          ],
        ],
      ];
    }

    return $filters;
  }

  /**
   * Builds geo_bounding_box filters from the boundary option.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Search API query.
   *
   * @return array
   *   Elasticsearch filter clauses.
   */
  protected function boundaryFilters(QueryInterface $query): array {
    $filters = [];
    $corners = ['lat_north_east', 'lng_north_east', 'lat_south_west', 'lng_south_west'];

    foreach ((array) $query->getOption('geolocation_boundary', []) as $boundary) {
      if (!is_array($boundary) || empty($boundary['field'])) {
        continue;
      }

      foreach ($corners as $corner) {
        if (!is_numeric($boundary[$corner] ?? NULL)) {
          continue 2;
        }
      }

      $filters[] = [
        'geo_bounding_box' => [
          $boundary['field'] => [
            'top_left' => [
              'lat' => (float) $boundary['lat_north_east'],
              'lon' => (float) $boundary['lng_south_west'],
            ],
            'bottom_right' => [
              'lat' => (float) $boundary['lat_south_west'],
              'lon' => (float) $boundary['lng_north_east'],
            ],
          ],
        ],
      ];
    }

    return $filters;
  }

  /**
   * Builds _geo_distance sorts for the distance pseudo fields.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Search API query.
   *
   * @return array
   *   Elasticsearch sort clauses.
   */
  protected function distanceSorts(QueryInterface $query): array {
    $sorts = [];

    $centers = [];
    foreach ($this->spatials($query) as $spatial) {
      $centers[$spatial['field']] = [
        'lat' => (float) $spatial['lat'],
        'lon' => (float) $spatial['lon'],
      ];
    }

    foreach ($query->getSorts() as $field => $direction) {
      // Distance pseudo field, named as Search API Location expects it.
      // @see search_api_location_views_views_data_alter()
      $field = preg_replace('/__distance$/', '', (string) $field, -1, $matched);
      if (!$matched || !isset($centers[$field])) {
        continue;
      }

      $sorts[] = [
        '_geo_distance' => [
          $field => $centers[$field],
          'unit' => 'km',
          'order' => strtolower($direction) === 'desc' ? 'desc' : 'asc',
        ],
      ];
    }

    return $sorts;
  }

  /**
   * Builds script fields returning each result's distance to the center.
   *
   * Sort values carry the distance only while actually sorting by it, so it is
   * calculated separately to stay available for display.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Search API query.
   *
   * @return array
   *   Elasticsearch script field definitions.
   */
  protected function distanceScriptFields(QueryInterface $query): array {
    $script_fields = [];
    $index_fields = $query->getIndex()->getFields();

    foreach ($this->spatials($query) as $spatial) {
      // arcDistance() resolves on geo_point doc values only, a geometry field
      // would fail the whole search.
      $type = $index_fields[$spatial['field']] ?? NULL;
      if (!$type || !in_array($type->getType(), ['geolocation_coordinates', 'location'])) {
        continue;
      }

      // The Solr backend names its distance pseudo field the same way.
      // @see \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend::getBackendDefinedFields()
      $script_fields[$spatial['field'] . '__distance'] = [
        'script' => [
          'source' => 'doc[params.field].size() == 0 ? null : doc[params.field].arcDistance(params.lat, params.lon) / 1000',
          'params' => [
            'field' => $spatial['field'],
            'lat' => (float) $spatial['lat'],
            'lon' => (float) $spatial['lon'],
          ],
        ],
      ];
    }

    return $script_fields;
  }

  /**
   * Returns the usable entries of the proximity option.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Search API query.
   *
   * @return array
   *   Spatial definitions carrying a field and a center.
   */
  protected function spatials(QueryInterface $query): array {
    $spatials = [];

    foreach ((array) $query->getOption('search_api_location', []) as $spatial) {
      if (
        !is_array($spatial)
        || empty($spatial['field'])
        || !is_numeric($spatial['lat'] ?? NULL)
        || !is_numeric($spatial['lon'] ?? NULL)
      ) {
        continue;
      }

      $spatials[] = $spatial;
    }

    return $spatials;
  }

  /**
   * Wraps the built query so that filter clauses can be added to it.
   *
   * The query is a bool, bare search params, absent, or wrapped in
   * function_score. Wrapping instead of appending covers each of those, and
   * filters neither score nor prevent caching.
   *
   * @param mixed $existing
   *   Query as built so far, if any.
   * @param array $filters
   *   Elasticsearch filter clauses.
   *
   * @return array
   *   Query including the filters.
   */
  protected function withFilters(mixed $existing, array $filters): array {
    $bool = [];

    if (!empty($existing)) {
      $bool['must'] = [$existing];
    }
    $bool['filter'] = $filters;

    return ['bool' => $bool];
  }

  /**
   * Converts the sort built by the backend into a list.
   *
   * Field names are keys there, leaving no room for repeated _geo_distance
   * clauses.
   *
   * @param array $sort
   *   Sort as built so far.
   *
   * @return array
   *   Sort as a list of single-clause arrays.
   */
  protected function sortToList(array $sort): array {
    $list = [];

    foreach ($sort as $field => $direction) {
      $list[] = is_int($field) ? $direction : [$field => $direction];
    }

    return $list;
  }

}
