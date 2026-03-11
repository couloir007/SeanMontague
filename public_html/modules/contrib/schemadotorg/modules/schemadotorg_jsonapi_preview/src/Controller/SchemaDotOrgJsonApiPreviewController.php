<?php

namespace Drupal\schemadotorg_jsonapi_preview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg_jsonapi_preview\SchemaDotOrgJsonApiPreviewBuilderInterface;

/**
 * Returns responses for Schema.org JSON:API preview.
 */
class SchemaDotOrgJsonApiPreviewController extends ControllerBase {

  /**
   * Constructs a SchemaDotOrgJsonApiPreviewController object.
   *
   * @param \Drupal\schemadotorg_jsonapi_preview\SchemaDotOrgJsonApiPreviewBuilderInterface $builder
   *   The Schema.org JSON-LD preview builder.
   */
  public function __construct(
    protected SchemaDotOrgJsonApiPreviewBuilderInterface $builder,
  ) {}

  /**
   * Builds the response containing the Schema.org JSON:API preview.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   *
   * @return array
   *   A renderable array containing the Schema.org JSON:API preview.
   */
  public function index(NodeInterface $node): array {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->entityTypeManager()->getStorage('node_type')->load($node->getType());
    return $this->builder->build($node)
      ?? ['#markup' => $this->t('A JSON:API endpoint is not enabled for the %label content type', ['%label' => $node_type->label()])];
  }

  /**
   * Get the node's title.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   *
   * @return string
   *   The node's title.
   */
  public function getTitle(NodeInterface $node): string {
    return $node->label();
  }

}
