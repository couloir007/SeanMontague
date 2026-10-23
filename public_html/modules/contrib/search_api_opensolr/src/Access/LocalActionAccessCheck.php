<?php

namespace Drupal\search_api_opensolr\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\Traits\OpensolrServerTrait;

/**
 * Checks access for displaying Solr configuration generator actions.
 */
class LocalActionAccessCheck implements AccessInterface {
  use OpensolrServerTrait;

  /**
   * Checks if the current server backend is an opensolr one.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\search_api\ServerInterface $search_api_server
   *   (optional) The Search API server entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns access allowed result if the server is using the opensolr
   *   services, forbidden otherwise.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function access(AccountInterface $account, ?ServerInterface $search_api_server = NULL) {
    if ($this->isOpenSolrServer($search_api_server)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
