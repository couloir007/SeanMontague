<?php

namespace Drupal\search_api_opensolr_security\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr_security\ServerSecurityOperations;

/**
 * Hook implementations for search_api_opensolr_security.
 *
 * Drupal 11.1+ discovers the #[Hook] attribute directly; on Drupal 10 the
 * #[LegacyHook] wrapper in search_api_opensolr_security.module forwards here.
 */
class SearchApiOpensolrSecurityHooks {

  /**
   * Constructs the hook implementations object (autowired).
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver, used to instantiate the operations helper with DI.
   */
  public function __construct(protected ClassResolverInterface $classResolver) {}

  /**
   * Implements hook_entity_operation().
   *
   * Adds the "Manage Security" operation on opensolr Search API servers.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are built for.
   * @param \Drupal\Core\Cache\CacheableMetadata|null $cacheability
   *   Cacheability of the operations list; added to the hook signature in
   *   Drupal 11.3 and therefore optional so the Drupal 10 wrapper still works.
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity, ?CacheableMetadata $cacheability = NULL) {
    if ($entity instanceof ServerInterface) {
      return $this->classResolver
        ->getInstanceFromDefinition(ServerSecurityOperations::class)
        ->entityOperation($entity);
    }
    return [];
  }

}
