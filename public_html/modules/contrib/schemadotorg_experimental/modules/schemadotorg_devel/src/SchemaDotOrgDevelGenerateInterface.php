<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_devel;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * The Schema.org devel generate interface.
 */
interface SchemaDotOrgDevelGenerateInterface {

  /**
   * Manages node.add and test local tasks.
   *
   * @param array $data
   *   An associative array containing list of (up to 2) tab levels that contain a
   *   list of tabs keyed by their href, each one being an associative array
   *   as described above.
   * @param string $route_name
   *   The route name of the page.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   The cacheability metadata for the current route's local tasks.
   */
  public function menuLocalTasksAlter(array &$data, string $route_name, RefinableCacheableDependencyInterface $cacheability): void;

  /**
   * Alter Mercury Editor node form.
   *
   * @param array &$form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  public function nodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void;

  /**
   * Acts on a node being created.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   */
  public function nodeCreate(NodeInterface $node): void;

  /**
   * Acts on a node before it is saved.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   */
  public function nodePresave(NodeInterface $node): void;

  /**
   * Acts on an entity before is saved.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   */
  public function entityPresave(EntityInterface $entity): void;

  /**
   * Preprocess node variables.
   *
   * @param array $variables
   *   The node variables.
   */
  public function preprocessNode(array &$variables): void;

  /**
   * Delete orphaned paragraphs that were created via devel generate.
   *
   * Deletes paragraphs that do not have a node as the top level parent entity.
   */
  public function deleteOrphanedParagraphs(): void;

  /**
   * Track orphaned paragraphs that are being generated.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity.
   */
  public function trackOrphanedParagraphs(ContentEntityInterface $entity): void;

  /**
   * Checks if the current user has the permission to generate Schema.org content.
   *
   * @return bool
   *   TRUE if the current user has the permission, FALSE otherwise.
   */
  public function hasDevelGeneratePermission(): bool;

  /**
   * Checks if the current request is to generate content.
   *
   * @return bool
   *   TRUE if the current request is to generate content.
   */
  public function isDevelGenerateRequest(): bool;

  /**
   * Checks if the current request is using GET.
   *
   * @return bool
   *   TRUE if the current request is using GET.
   */
  public function isGet(): bool;

}
