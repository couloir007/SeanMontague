<?php

namespace Drupal\search_api_opensolr_security;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\Traits\OpensolrServerTrait;

/**
 * Defines a class for reacting to server events.
 */
class ServerSecurityOperations {
  use OpensolrServerTrait;
  use StringTranslationTrait;

  /**
   * Reacts on entity operation hook.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   The newly created server.
   *
   * @return array
   *   Returns an array with the entity operations.
   *
   * @throws \Drupal\search_api\SearchApiException
   *
   * @see hook_entity_insert()
   */
  public function entityOperation(ServerInterface $server) {
    $operations = [];
    if ($this->isOpenSolrServer($server)) {
      $operations['opensolr_security'] = [
        'title' => $this->t('Manage Security'),
        'url' => Url::fromRoute('search_api_opensolr_security.manage_security', ['search_api_server' => $server->id()]),
        'weight' => 50,
      ];
    }
    return $operations;
  }

}
