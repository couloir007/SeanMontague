<?php

namespace Drupal\search_api_opensolr\Hook;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\ServerOperations;
use Drupal\search_api_opensolr\ThemeOperations;

/**
 * Hook implementations for search_api_opensolr.
 *
 * Drupal 11.1+ discovers the #[Hook] attributes below directly; on Drupal 10
 * the #[LegacyHook] wrappers in search_api_opensolr.module forward here, so
 * the logic lives in exactly one place for both major versions.
 */
class SearchApiOpensolrHooks {

  /**
   * Constructs the hook implementations object (autowired).
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver, used to instantiate the operation helpers with DI.
   */
  public function __construct(protected ClassResolverInterface $classResolver) {}

  /**
   * Implements hook_ENTITY_TYPE_insert() for search_api_server.
   *
   * @see \Drupal\search_api_opensolr\ServerOperations::serverInsert()
   */
  #[Hook('search_api_server_insert')]
  public function searchApiServerInsert(ServerInterface $entity) {
    return $this->classResolver
      ->getInstanceFromDefinition(ServerOperations::class)
      ->serverInsert($entity);
  }

  /**
   * Implements hook_theme().
   *
   * @see \Drupal\search_api_opensolr\ThemeOperations::theme()
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return $this->classResolver
      ->getInstanceFromDefinition(ThemeOperations::class)
      ->theme($existing, $type, $theme, $path);
  }

}
