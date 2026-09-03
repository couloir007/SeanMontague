<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_mermaid_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns Mermaid test fixtures.
 */
class SchemaDotOrgMermaidTestController extends ControllerBase {

  /**
   * Builds a Mermaid diagram test page.
   *
   * @return array
   *   A render array containing a Mermaid diagram.
   */
  public function diagram(): array {
    $diagram = <<<MERMAID
flowchart LR
  Alpha[Alpha] --> Bravo[Bravo]
  Bravo --> Charlie[Charlie]
  Charlie --> Delta[Delta]
  Delta --> Echo[Echo]
  Echo --> Foxtrot[Foxtrot]
  Foxtrot --> Golf[Golf]
  Golf --> Hotel[Hotel]
  Hotel --> India[India]
  India --> Juliet[Juliet]
MERMAID;

    $build['diagram'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mermaid', 'mermaid-download', 'schemadotorg-mermaid', 'schemadotorg-mermaid-test-diagram']],
      '#markup' => $diagram,
    ];

    $build['disabled'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['mermaid', 'schemadotorg-mermaid', 'schemadotorg-mermaid-test-disabled'],
        'data-schemadotorg-mermaid-panzoom' => 'false',
      ],
      '#markup' => $diagram,
    ];

    $build['#attached']['library'][] = 'schemadotorg/schemadotorg.mermaid';

    return $build;
  }

}
