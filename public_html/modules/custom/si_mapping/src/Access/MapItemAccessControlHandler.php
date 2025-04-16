<?php

namespace Drupal\si_mapping\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for Map Item entity.
 */
class MapItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * Checks access for a given operation on a Map Item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Map Item entity.
   * @param string $operation
   *   The operation being performed ("view", "update", or "delete").
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // If the operation is 'view' and the account is anonymous, deny access.
    if ($operation === 'view' && $account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    // Otherwise, defer to the default checks (which might include field-level or permission-based access).
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * Checks create access for Map Item entities.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param array $context
   *   Additional context for access checks.
   * @param string|null $bundle
   *   The bundle for the new entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $bundle = NULL) {
    // Defer to the parent for create operations.
    return parent::checkCreateAccess($account, $context, $bundle);
  }
}
