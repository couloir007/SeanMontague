<?php

namespace Drupal\geolocation_search_api\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
use Drupal\geolocation\Plugin\views\filter\ProximityFilter;

/**
 * Filter handler for search keywords.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter(id: 'geolocation_search_api_filter_proximity')]
class GeolocationSearchApiFilterProximity extends ProximityFilter {

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $this->value['value'] = self::convertDistance($this->value['value'], $this->options['unit']);

    $center = $this->locationInputManager->getCoordinates((array) $this->value['center'], $this->options['location_input'], ['views_filter' => $this]);

    if (
      empty($center)
      || !is_numeric($center['lat'])
      || !is_numeric($center['lng'])
      || empty($this->value['value'])
    ) {
      return;
    }

    /** @var \Drupal\search_api\Query\Query $query */
    $query = $this->query;

    $location_options = (array) $query->getOption('search_api_location', []);

    $location_options[] = [
      'field' => $this->realField,
      'lat' => $center['lat'],
      'lon' => $center['lng'],
      'radius' => $this->value['value'],
    ];
    $query->setOption('search_api_location', $location_options);
  }

}
