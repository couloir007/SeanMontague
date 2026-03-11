<?php

namespace Drupal\schemadotorg_jsonld_preview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;
use Drupal\schemadotorg_jsonld_preview\SchemaDotOrgJsonLdPreviewBuilderInterface;

/**
 * Returns responses for Schema.org JSON-LD preview.
 */
class SchemaDotOrgJsonLdPreviewController extends ControllerBase {

  /**
   * Constructs a SchemaDotOrgJsonLdPreviewController object.
   *
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $manager
   *   The Schema.org JSON-LD manager.
   * @param \Drupal\schemadotorg_jsonld_preview\SchemaDotOrgJsonLdPreviewBuilderInterface $builder
   *   The Schema.org JSON-LD preview builder.
   */
  public function __construct(
    protected SchemaDotOrgJsonLdManagerInterface $manager,
    protected SchemaDotOrgJsonLdPreviewBuilderInterface $builder,
  ) {}

  /**
   * Builds the response containing the Schema.org JSON-LD preview.
   *
   * @param string $format
   *   The format of the JSON-LD preview.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   *
   * @return array
   *   A renderable array containing the Schema.org JSON-LD preview.
   */
  public function index(string $format, NodeInterface $node): array {
    $route_match = $this->manager->getEntityRouteMatch($node);
    return $this->builder->build($format, $route_match);
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
