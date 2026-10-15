<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_javascript_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns jsTree test fixtures.
 */
class SchemaDotOrgJavaScriptTestJsTreeController extends ControllerBase {

  /**
   * Builds a jsTree test page.
   *
   * @return array
   *   A render array containing a linked tree.
   */
  public function tree(): array {
    $build['tree'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'schemadotorg-javascript-test-jstree'],
      '#markup' => '<ul class="schemadotorg-jstree"><li id="schemadotorg-javascript-test-jstree-thing"><div><a href="#Thing">Thing</a></div><ul><li id="schemadotorg-javascript-test-jstree-person"><div><a href="#Person">Person</a></div></li></ul></li></ul>',
    ];
    $build['#attached']['library'][] = 'schemadotorg/schemadotorg.jstree';

    return $build;
  }

}
