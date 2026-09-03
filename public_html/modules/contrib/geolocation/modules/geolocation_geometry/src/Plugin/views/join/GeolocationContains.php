<?php

namespace Drupal\geolocation_geometry\Plugin\views\join;

use Drupal\views\Attribute\ViewsJoin;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\views\join\JoinPluginInterface;

/**
 * Geometry joins.
 *
 * @ingroup views_join_handlers
 */
#[ViewsJoin(id: 'geolocation_contains')]
class GeolocationContains extends JoinPluginBase implements JoinPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select_query
   *   Select query.
   * @param string|array $table
   *   Table data.
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $view_query
   *   View query.
   */
  public function buildJoin($select_query, $table, $view_query): void {
    $geometry_field = $table['alias'] . '.' . $this->field . '_geometry';
    $latitude_field = $this->leftTable . '.' . $this->leftField . '_lat';
    $longitude_field = $this->leftTable . '.' . $this->leftField . '_lng';

    $condition = "ST_Contains(" . $geometry_field . ", ST_PointFromText(CONCAT('POINT(', " . $longitude_field . ", ' ', " . $latitude_field . ", ')'), 4326))";

    $select_query->addJoin($this->type, $this->table, $table['alias'], $condition);
  }

}
