<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Schema.org CodeMirror JavaScript behavior.
 *
 * @group schemadotorg
 */
class SchemaDotOrgCodeMirrorJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_javascript_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests CodeMirror editor and preformatted-code behavior.
   */
  public function testCodeMirrorJavaScript(): void {
    $this->drupalGet('schemadotorg/test/javascript/form');

    // Check that the textarea becomes one configured YAML editor.
    $this->assertJsCondition(<<<'JS'
(function () {
  const textarea = document.querySelector('#schemadotorg-javascript-test-codemirror');
  const editor = textarea.nextElementSibling;
  return typeof window.CodeMirror === 'function'
    && typeof window.CodeMirror.runMode === 'function'
    && textarea.style.display === 'none'
    && editor.classList.contains('CodeMirror')
    && editor.CodeMirror.getValue() === 'name: Example\ntype: Thing\n'
    && editor.CodeMirror.getOption('mode') === 'text/x-yaml'
    && editor.CodeMirror.getOption('lineNumbers')
    && editor.CodeMirror.getOption('matchBrackets');
}())
JS);

    // Check that the YAML example receives CodeMirror token markup.
    $this->assertJsCondition(<<<'JS'
(function () {
  const example = document.querySelector('#schemadotorg-javascript-test-codemirror-example');
  return example.classList.contains('cm-s-default')
    && example.classList.contains('schemadotorg-codemirror-mode')
    && example.textContent === 'name: Example\ntype: Thing\n'
    && example.querySelector('span[class^="cm-"], span[class*=" cm-"]') !== null;
}())
JS);

    // Check that reattaching behaviors does not duplicate CodeMirror output.
    $this->assertJsCondition(<<<'JS'
(function () {
  const example = document.querySelector('#schemadotorg-javascript-test-codemirror-example');
  const exampleHtml = example.innerHTML;
  const editorCount = document.querySelectorAll('.CodeMirror').length;
  Drupal.attachBehaviors(document);
  return document.querySelectorAll('.CodeMirror').length === editorCount
    && example.innerHTML === exampleHtml;
}())
JS);
  }

}
