<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_sidebar;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org sidebar manager interface.
 */
interface SchemaDotOrgSidebarManagerInterface {

  /**
   * Add sidebar to a mapping is inserted.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void;

  /**
   * Alter sidebar the inline entity widget form to remove the nested fieldset.
   *
   * @param array $element
   *   The field widget form element as constructed by
   *   \Drupal\Core\Field\WidgetBaseInterface::form().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $context
   *   An associative array.
   */
  public function fieldWidgetSingleElementInlineEntityFormSimpleFormAlter(array &$element, FormStateInterface $form_state, array $context): void;

  /**
   * Alter a node being viewed to determined if sidebar should be visible.
   *
   * @param array &$build
   *   A renderable array representing the entity content.
   * @param \Drupal\node\NodeInterface $node
   *   The node being rendered.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options configured for the
   *   entity components.
   */
  public function nodeViewAlter(array &$build, NodeInterface $node, EntityViewDisplayInterface $display): void;

}
