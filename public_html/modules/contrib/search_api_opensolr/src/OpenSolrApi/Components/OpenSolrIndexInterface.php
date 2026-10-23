<?php

namespace Drupal\search_api_opensolr\OpenSolrApi\Components;

/**
 * Provides an interface defining an OpenSolrIndex component.
 *
 * @package Drupal\search_api_opensolr\OpenSolrApi\Components
 */
interface OpenSolrIndexInterface {

  /**
   * Get a list of the user's Opensolr Indexes.
   *
   * @param array $params
   *   An array with the params to send to the API call.
   * @param bool $excludeReplicas
   *   A boolean indicating if we should exclude replicas from the list.
   *
   * @return mixed
   *   Returns the array with the response data.
   */
  public function getIndexList(array $params = [], $excludeReplicas = TRUE);

  /**
   * Gets the info for the given index name.
   *
   * @param string $coreName
   *   The id of the index.
   *
   * @return mixed
   *   Returns the array with the response data.
   */
  public function getCoreInfo($coreName);

  /**
   * Reloads an index - also used for troubleshooting.
   *
   * @param string $coreName
   *   The id of the index.
   *
   * @return bool|array
   *   Returns TRUE if the core was reloaded okay, the result array with the
   *   status and the error message otherwise.
   */
  public function reloadCore($coreName);

  /**
   * Optimizes an index - also used for troubleshooting.
   *
   * @param string $coreName
   *   The id of the index.
   *
   * @return mixed
   *   Returns the array with the response data.
   */
  public function optimizeCore($coreName);

  /**
   * Commits data for a SOLR index.
   *
   * @param string $coreName
   *   The id of the index.
   *
   * @return mixed
   *   Returns the array with the response data.
   */
  public function commitData($coreName);

  /**
   * Gets full status of a SOLR index.
   *
   * @param string $coreName
   *   The id of the index.
   *
   * @return mixed
   *   Returns the array with the response data.
   */
  public function getCoreStatus($coreName);

  /**
   * Creates a new SOLR index or SolrCloud Collection.
   *
   * @param string $coreName
   *   The id of the index.
   * @param string $region
   *   The server identifier, in which the solr core should be created.
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   Returns the array with the response data.
   */
  public function createCore($coreName, $region);

  /**
   * Deletes an OpenSolr index.
   *
   * @param string $coreName
   *   The id of the index.
   *
   * @return mixed
   *   Returns the array with the response data.
   */
  public function deleteCore($coreName);

  /**
   * Replicates an OpenSolr index.
   *
   * @param string $coreName
   *   The name of the master index.
   * @param string $targetIndex
   *   The name of the target replica index (must exist).
   *
   * @return mixed
   *   Returns the array with the response data.
   */
  public function replicateIndex($coreName, $targetIndex);

  /**
   * Gets the environments as opensolr response object.
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   The opensolr response object containing the data if successful.
   */
  public function getEnvironments();

}
