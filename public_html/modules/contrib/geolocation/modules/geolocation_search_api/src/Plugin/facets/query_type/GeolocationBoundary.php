<?php

namespace Drupal\geolocation_search_api\Plugin\facets\query_type;

use Drupal\facets\FacetInterface;
use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\geolocation_search_api\Plugin\views\filter\GeolocationSearchApiFilterBoundary;

/**
 * Restricts the search to a map boundary.
 *
 * Unlike term facets there is no fixed set of values to choose from, so no
 * facet counts are requested from the backend. The facet carries two results
 * instead: the active boundary, if any, whose URL removes it again, and a
 * placeholder, whose URL the map widget fills in with the map boundaries.
 *
 * Values are formatted "north,east,south,west".
 *
 * On Solr, coordinate fields ("loc" prefix) require search_api_solr to leave
 * out the heatmap for non-RPT fields, geometry fields work regardless.
 *
 * @see https://www.drupal.org/project/search_api_solr/issues/3448108
 *
 * @FacetsQueryType(
 *   id = "geolocation_boundary",
 *   label = @Translation("Geolocation boundary"),
 * )
 */
class GeolocationBoundary extends QueryTypePluginBase {

  /**
   * Raw value of the result standing in for the boundary to be set.
   */
  const PLACEHOLDER = '__geolocation_boundary__';

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $boundary = static::getActiveBoundary($this->facet->getActiveItems());
    if (!$boundary) {
      return;
    }

    GeolocationSearchApiFilterBoundary::addBoundaryOptions(
      $this->query,
      $this->facet->getFieldIdentifier(),
      $boundary['north'],
      $boundary['east'],
      $boundary['south'],
      $boundary['west']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): FacetInterface {
    // A new boundary replaces the active one instead of adding to it.
    // @see \Drupal\facets\Plugin\facets\url_processor\QueryString::buildUrls()
    $this->facet->setShowOnlyOneResult(TRUE);

    $results = [];

    // Invalid and superseded values included, so a new boundary drops them.
    foreach (array_unique($this->facet->getActiveItems()) as $active_value) {
      $active = new Result($this->facet, $active_value, $active_value, 0);
      $active->setActiveState(TRUE);
      $results[] = $active;
    }

    $results[] = new Result($this->facet, static::PLACEHOLDER, static::PLACEHOLDER, 0);

    $this->facet->setResults($results);

    return $this->facet;
  }

  /**
   * Returns the raw value of the active boundary.
   *
   * @param array $active_items
   *   Active facet items.
   *
   * @return string|null
   *   The last valid value, the one added to the URL most recently.
   */
  public static function getActiveValue(array $active_items): ?string {
    foreach (array_reverse($active_items) as $value) {
      if (is_string($value) && static::parseBoundary($value)) {
        return $value;
      }
    }

    return NULL;
  }

  /**
   * Returns the active boundary.
   *
   * @param array $active_items
   *   Active facet items.
   *
   * @return array|null
   *   Boundary keyed north, east, south and west.
   */
  public static function getActiveBoundary(array $active_items): ?array {
    $value = static::getActiveValue($active_items);

    return $value === NULL ? NULL : static::parseBoundary($value);
  }

  /**
   * Parses a facet value into a boundary.
   *
   * @param string $value
   *   Facet value.
   *
   * @return array|null
   *   Boundary keyed north, east, south and west, NULL if invalid.
   */
  public static function parseBoundary(string $value): ?array {
    $parts = explode(',', $value);
    if (count($parts) !== 4) {
      return NULL;
    }

    foreach ($parts as $part) {
      if (!is_numeric($part)) {
        return NULL;
      }
    }

    [$north, $east, $south, $west] = array_map('floatval', $parts);

    if (
      $north < $south
      || $north > 90
      || $south < -90
      || $east > 180
      || $west < -180
    ) {
      return NULL;
    }

    return [
      'north' => $north,
      'east' => $east,
      'south' => $south,
      'west' => $west,
    ];
  }

}
