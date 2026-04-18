<?php

declare(strict_types=1);

namespace Drupal\paragraphs_usage;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user')
    );
  }

  /**
   * Adds paragraphs usage links to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types): void {
    if (isset($entity_types['paragraphs_type'])) {
      /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $paragraphs_type */
      $paragraphs_type = $entity_types['paragraphs_type'];
      $paragraph_type_id = $paragraphs_type->id();
      // Add 'paragraphs-usage' to link templates.
      $paragraphs_type->setLinkTemplate('paragraphs-usage', "/admin/structure/$paragraph_type_id/{entity_type_id}/usage");
    }
  }

  /**
   * Adds paragraphs usage operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function entityOperation(EntityInterface $entity): array {
    $operations = [];
    if (
      ($entity->getEntityTypeId() === "paragraphs_type") &&
      ($entity->hasLinkTemplate('paragraphs-usage'))
    ) {
      $operations['paragraphs-usage'] = [
        'title' => $this->t('Usage'),
        'url' => $entity->toUrl('paragraphs-usage'),
        'weight' => 80,
      ];
    }
    return $operations;
  }

}
