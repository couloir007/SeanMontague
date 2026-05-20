<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The Schema.org additional type field access handler interface.
 */
interface SchemaDotOrgAdditionalTypeFieldAccessHandlerInterface {

  /**
   * Control access to fields.
   *
   * @param string $operation
   *   The operation to be performed.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param \Drupal\Core\Field\FieldItemListInterface|null $items
   *   (optional) The entity field object for which to check access, or NULL if
   *   access is checked for the field definition, without any specific value
   *   available. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function entityFieldAccess(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResult;

  /**
   * Resets the cache for entity-related operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity for which the cache should be reset. If NULL, the entire
   *   cache is cleared. If it is a ContentEntityInterface, only the cache for
   *   that specific entity is cleared.
   */
  public function resetCache(?EntityInterface $entity = NULL): void;

}
