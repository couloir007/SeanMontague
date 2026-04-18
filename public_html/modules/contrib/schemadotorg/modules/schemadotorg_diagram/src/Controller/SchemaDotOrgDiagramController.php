<?php

namespace Drupal\schemadotorg_diagram\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg_diagram\SchemaDotOrgDiagramBuilderInterface;

/**
 * Returns responses for Schema.org Diagram.
 */
class SchemaDotOrgDiagramController extends ControllerBase {

  /**
   * Constructs a SchemaDotOrgDiagramController objects.
   *
   * @param \Drupal\schemadotorg_diagram\SchemaDotOrgDiagramBuilderInterface $schemaDiagramBuilder
   *   The Schema.org Diagram service.
   */
  public function __construct(
    protected SchemaDotOrgDiagramBuilderInterface $schemaDiagramBuilder,
  ) {}

  /**
   * Builds the response containing the Schema.org diagrams.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   *
   * @return array
   *   A renderable array containing the Schema.org diagrams.
   */
  public function index(NodeInterface $node): array {
    $diagrams = $this->schemaDiagramBuilder->buildDiagrams($node);
    if (empty($diagrams)) {
      return [
        '#markup' => $this->t('There are no diagrams available.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }
    return $diagrams;
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
