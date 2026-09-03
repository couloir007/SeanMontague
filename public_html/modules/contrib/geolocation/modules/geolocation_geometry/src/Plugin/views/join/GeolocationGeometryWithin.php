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
#[ViewsJoin(id: 'geolocation_geometry_within')]
class GeolocationGeometryWithin extends JoinPluginBase implements JoinPluginInterface {

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
    /** @var \Drupal\Core\Database\Query\Select $select_query */

    $geometry_field = ($table['alias'] ?: $this->table) . '.' . $this->field . '_geometry';
    $within_field = $this->leftTable . '.' . $this->leftField . '_geometry';
    $condition = 'ST_Within(' . $within_field . ', ' . $geometry_field . ')';

    $select_query->addJoin($this->type, $this->table, $table['alias'], $condition);
  }

}
