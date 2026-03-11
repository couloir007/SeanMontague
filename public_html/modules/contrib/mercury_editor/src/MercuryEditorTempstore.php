<?php

namespace Drupal\mercury_editor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Layout Paragraphs Layout Tempstore Repository class definition.
 */
class MercuryEditorTempstore {

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStoreFactory;

  /**
   * LayoutTempstoreRepository constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The shared tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory->get('mercury_editor');
  }

  /**
   * Get a content entity from the tempstore.
   *
   * @param string $uuid
   *   The uuid of the content entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A content entity.
   */
  public function get($uuid) {
    return $this->tempStoreFactory->get($uuid);
  }

  /**
   * Save a content entity to the tempstore.
   */
  public function set(ContentEntityInterface $entity) {
    $this->tempStoreFactory->set($entity->uuid(), $entity);
    return $entity;
  }

  /**
   * Delete a content entity from the tempstore.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to delete.
   */
  public function delete(ContentEntityInterface $entity) {
    $this->tempStoreFactory->delete($entity->uuid());
  }

}
