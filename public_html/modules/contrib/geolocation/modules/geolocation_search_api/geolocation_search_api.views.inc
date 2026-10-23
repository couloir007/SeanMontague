<?php

/**
 * @file
 * Provide Views integration for Search API Location.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data_alter().
 */
function geolocation_search_api_views_data_alter(array &$data): void {
  /** @var \Drupal\search_api\IndexInterface $index */
  foreach (Index::loadMultiple() as $index) {
    $table = &$data['search_api_index_' . $index->id()];

    /** @var \Drupal\search_api\Item\FieldInterface $field */
    foreach ($index->getFields(TRUE) as $field_id => $field) {
      if (!in_array($field->getType(), ['geolocation_coordinates', 'geolocation_geometry'])) {
        continue;
      }

      $field_alias = _geolocation_search_api_views_get_field_alias($field_id, $table);

      $field_alias_boundary = $field_alias . '_proximity';
      $table[$field_alias_boundary] = [
        'group' => $field->getLabel(),
        'title' => t('Proximity (@field_name)', ['@field_name' => $field_alias]),
        'help' => $field->getDescription(),
        'filter' => [
          'id' => 'geolocation_search_api_filter_proximity',
          'allow empty' => TRUE,
          'real field' => $field_alias,
        ],
      ];

      $field_alias_boundary = $field_alias . '_boundary';
      $table[$field_alias_boundary] = [
        'group' => $field->getLabel(),
        'title' => t('Boundary (@field_name)', ['@field_name' => $field_alias]),
        'help' => $field->getDescription(),
        'filter' => [
          'id' => 'geolocation_search_api_filter_boundary',
          'allow empty' => TRUE,
          'real field' => $field_alias,
        ],
      ];

      // A distance only exists for point data, and only relative to the center
      // a proximity filter sets. Backends declaring the pseudo field themselves
      // have already put it on the table, so leave theirs alone.
      // @see \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend::getBackendDefinedFields()
      $field_alias_distance = $field_alias . '_distance';
      if (
        $field->getType() !== 'geolocation_coordinates'
        || isset($table[$field_alias_distance])
      ) {
        continue;
      }

      $table[$field_alias_distance] = [
        'group' => $field->getLabel(),
        'title' => t('Distance (@field_name)', ['@field_name' => $field_alias]),
        'help' => t('Distance to the center of the proximity filter, in kilometers.'),
        'real field' => $field_id . '__distance',
        'field' => [
          'id' => 'search_api_numeric',
        ],
        'sort' => [
          'id' => 'search_api',
        ],
      ];
    }
  }
}

/**
 * Finds the field alias for a field in a Views table definition.
 *
 * @param string $field_id
 *   The original ID of the Search API field.
 * @param array $table
 *   The Views table definition.
 *
 * @return string|false
 *   The field alias of the field or FALSE.
 */
function _geolocation_search_api_views_get_field_alias($field_id, array $table) {
  // We need to determine the Views field alias based on the Search API
  // field_id.
  // We can't use _search_api_views_find_field_alias, as that would generate
  // a new name.
  $field_alias = FALSE;
  if (isset($table[$field_id])) {
    $field_alias = $field_id;
  }
  else {
    foreach ($table as $field_name => $field_info) {
      if (!empty($field_info['real field']) && $field_info['real field'] == $field_id) {
        $field_alias = $field_name;
        break;
      }
    }
  }

  return $field_alias;
}
