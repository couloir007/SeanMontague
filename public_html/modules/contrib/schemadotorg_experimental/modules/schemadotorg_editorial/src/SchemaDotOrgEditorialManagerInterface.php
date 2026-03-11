<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_editorial;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * The Schema.org editorial manager interface.
 */
interface SchemaDotOrgEditorialManagerInterface {

  /**
   * Performs operations on a node before it is saved.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being processed before saving.
   */
  public function nodePresave(NodeInterface $node): void;

  /**
   * Prepares the node form by displaying an editorial message if applicable.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being prepared.
   * @param string $operation
   *   The operation being performed on the node (e.g., "edit").
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function nodePrepareForm(NodeInterface $node, string $operation, FormStateInterface $form_state): void;

  /**
   * Alters the node view build array to conditionally hide the editorial paragraph.
   *
   * @param array $build
   *   The renderable array representing the node's view.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being viewed.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display object controlling the view mode.
   */
  public function nodeViewAlter(array &$build, NodeInterface $node, EntityViewDisplayInterface $display): void;

}
