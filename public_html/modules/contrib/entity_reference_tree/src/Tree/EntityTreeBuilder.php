<?php

namespace Drupal\entity_reference_tree\Tree;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a class for building a tree from general entity.
 *
 * @ingroup entity_reference_tree_api
 *
 * @see \Drupal\entity_reference_tree\Tree\TreeBuilderInterface
 */
class EntityTreeBuilder implements TreeBuilderInterface {

  /**
   * Maximum number of entities loaded for a tree request.
   *
   * Hard cap prevents expensive unbounded loads for wildcard bundles.
   */
  private const MAX_TREE_ENTITIES = 1000;

  /**
   * The permission name to access the entity tree.
   *
   * The entity storage load function is actually responsible for
   * the permission checking for each individual entity.
   * So here just use a very weak permission.
   *
   * @var string
   */
  private $accessPermission = 'access content';

  /**
   * The Language code.
   *
   * @var string
   */
  protected $langCode;

  /**
   * Load all entities from an entity bundle for the tree.
   *
   * @param string $entityType
   *   The type of the entity.
   * @param string $bundleID
   *   The bundle ID.
   * @param string|null $langCode
   *   The language code.
   * @param int $parent
   *   The parent ID.
   * @param int|null $max_depth
   *   The maximum depth.
   *
   * @return array
   *   All entities in the entity bundle.
   */
  public function loadTree(string $entityType, string $bundleID, ?string $langCode = NULL, int $parent = 0, ?int $max_depth = NULL) {
    if ($this->hasAccess()) {
      $entityStorage = \Drupal::entityTypeManager()->getStorage($entityType);
      if ($bundleID === '*') {
        // Load entities from all bundles, bounded by a hard cap.
        $entities = $this->loadTreeEntities($entityStorage, NULL);
        $hasBundle = FALSE;
        $tree = [];
      }
      else {
        $hasBundle = TRUE;
        // Build the tree node for the bundle.
        $tree = [
          (object) [
            'id' => $bundleID,
            // Required.
            'parent' => '#',
            // Node text.
            'text' => $bundleID,
            // It is a bundle node.
            'isBundle' => TRUE,
          ],
        ];
        // Load entities in the selected bundle, bounded by a hard cap.
        $entities = $this->loadTreeEntities($entityStorage, $bundleID);
      }

      // Build the tree.
      foreach ($entities as $entity) {
        if ($entity->access('view')) {
          $tree[] = (object) [
            'id' => $entity->id(),
            // Required.
            'parent' => $hasBundle ? $entity->bundle() : '#',
            // Node text.
            'text' => $entity->label(),
          ];
        }
      }

      return $tree;
    }
    // The user does not have the permission.
    return NULL;
  }

  /**
   * Load entities for tree rendering with a hard upper bound.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entityStorage
   *   Storage for the target entity type.
   * @param string|null $bundleID
   *   Bundle ID to filter by. Use NULL for all bundles.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Loaded entities keyed by ID.
   */
  private function loadTreeEntities(EntityStorageInterface $entityStorage, ?string $bundleID): array {
    $query = $entityStorage
      ->getQuery()
      ->accessCheck(TRUE)
      ->range(0, self::MAX_TREE_ENTITIES);

    if ($bundleID !== NULL) {
      $bundle_key = $entityStorage->getEntityType()->getKey('bundle');
      if (empty($bundle_key)) {
        return [];
      }
      $query->condition($bundle_key, $bundleID);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    return $entityStorage->loadMultiple($ids);
  }

  /**
   * Create a tree node.
   *
   * @param object $entity
   *   The entity for the tree node.
   * @param array $selected
   *   An array for all selected nodes.
   *
   * @return array
   *   The tree node for the entity.
   */
  public function createTreeNode($entity, array $selected = []) {

    $node = [
    // Required.
      'id' => $entity->id,
    // Required.
      'parent' => $entity->parent,
    // Node text.
      'text' => $entity->text,
      'state' => ['selected' => FALSE],
    ];

    if (in_array($entity->id, $selected)) {
      // Initially selected node.
      $node['state']['selected'] = TRUE;
    }

    $is_bundle = $entity->isBundle ?? FALSE;
    if ($is_bundle) {
      $node['data'] = [
        'isBundle' => TRUE,
      ];
    }

    return $node;
  }

  /**
   * Get the ID of a tree node.
   *
   * @param object $entity
   *   The entity for the tree node.
   *
   * @return string|int|null
   *   The id of the tree node for the entity.
   */
  public function getNodeId($entity) {
    return $entity->id;
  }

  /**
   * Check if a user has the access to the tree.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface|null $user
   *   The user object to check.
   *
   * @return bool
   *   If the user has the access to the tree return TRUE,
   *   otherwise return FALSE.
   */
  private function hasAccess(?AccountProxyInterface $user = NULL) {
    // Check current user as default.
    if (empty($user)) {
      $user = \Drupal::currentUser();
    }

    return $user->hasPermission($this->accessPermission);
  }

}
