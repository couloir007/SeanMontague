<?php

namespace Drupal\search_api_opensolr;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\Services\ZipManager;
use Drupal\search_api_opensolr\Traits\OpensolrServerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to server events.
 */
class ServerOperations implements ContainerInjectionInterface {
  use OpensolrServerTrait;

  /**
   * The opensolr zip manager service.
   *
   * @var \Drupal\search_api_opensolr\Services\ZipManager
   */
  protected $zipManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  public function __construct(ZipManager $zipManager, MessengerInterface $messenger) {
    $this->zipManager = $zipManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search_api_opensolr.zip_manager'),
      $container->get('messenger')
    );
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for search_api_server.
   *
   * @see \search_api_opensolr_search_api_server_insert()
   */
  public function serverInsert(ServerInterface $server) {
    if ($this->isOpenSolrServer($server)) {
      $indexType = $this->getServerIndexType($server);
      if ($indexType < 0) {
        $this->zipManager->importConfigZip($server);
      }
      else {
        $this->messenger->addWarning('Make sure to manually upload the config zip to opensolr as the automatic upload is temporarily disabled for clusters.');
      }
    }
  }

}
