<?php

namespace Drupal\search_api_opensolr\Traits;

use Drupal\search_api\ServerInterface;
use Drupal\search_api_solr\SolrBackendInterface;

/**
 * Opensolr server helper methods.
 */
trait OpensolrServerTrait {

  /**
   * Checks if the given server uses an opensolr connector.
   *
   * This is an easier way of identifying the opensolr server types, since we
   * are using the solr functionality and we don't have our own type of backend.
   * Future improvement might be needed.
   *
   * @param \Drupal\search_api\ServerInterface|null $search_api_server
   *   The server entity object.
   *
   * @return bool
   *   Returns TRUE if the backend connector has the opensolr key, FALSE
   *   otherwise.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function isOpenSolrServer(?ServerInterface $search_api_server = NULL) {
    if ($search_api_server && $search_api_server->getBackend() instanceof SolrBackendInterface) {
      $backendConfig = $search_api_server->getBackendConfig();
      return !empty($backendConfig['connector_config']['opensolr']);
    }
    return FALSE;
  }

  /**
   * Gets the index type from an opensolr server.
   *
   * @param \Drupal\search_api\ServerInterface|null $search_api_server
   *   The server entity object.
   *
   * @return bool
   *   Returns -1 for regular index, 0 for cluster and >=1 for replicas.
   *   Returns FALSE if it's not an opensolr server.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function getServerIndexType(?ServerInterface $search_api_server = NULL) {
    if ($this->isOpenSolrServer($search_api_server)) {
      $backendConfig = $search_api_server->getBackendConfig();
      return $backendConfig['connector_config']['opensolr']['index_type'];
    }
    return FALSE;
  }

}
