<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\FieldFormatter;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for JavaScript-dependent formatter tests.
 */
abstract class FormatterJavascriptTestBase extends WebDriverTestBase {

  /**
   * The entity view display under test.
   */
  protected const DISPLAY_ID = 'node.custom_field_entity_test.default';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_test',
    'node',
    'field_ui',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The field name.
   *
   * @var string
   */
  protected string $fieldName = 'field_test';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer node display',
    ]);
  }

  /**
   * Returns the path to the manage display form.
   *
   * @return string
   *   The path to the manage display form.
   */
  protected function getManageDisplayPath(): string {
    return '/admin/structure/types/manage/custom_field_entity_test/display';
  }

  /**
   * Loads the entity view display under test.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The loaded display.
   */
  protected function loadDisplay(): EntityViewDisplay {
    $display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load(static::DISPLAY_ID);
    $this->assertInstanceOf(
      EntityViewDisplay::class,
      $display,
      'Entity view display ' . static::DISPLAY_ID . ' must exist.',
    );

    return $display;
  }

  /**
   * Opens the formatter settings form for the test field.
   *
   * @param string $ready_css
   *   Selector for an element that only exists once the settings form has
   *   rendered. Formatters differ here, so each test supplies its own.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element matched by $ready_css.
   */
  protected function openFormatterSettings(string $ready_css): NodeElement {
    $this->drupalGet($this->getManageDisplayPath());

    $field_name_hyphen = str_replace('_', '-', $this->fieldName);
    $this->clickElement(
      '[data-drupal-selector="edit-fields-' . $field_name_hyphen . '-settings-edit"]',
    );

    return $this->waitForVisible(
      $ready_css,
      "Formatter settings form did not open (waiting for: $ready_css).",
    );
  }

  /**
   * Waits for an element to become visible and asserts it was found.
   *
   * @param string $css
   *   The CSS selector.
   * @param string $message
   *   Assertion message used when the element never appears.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The visible element.
   */
  protected function waitForVisible(string $css, string $message): NodeElement {
    $element = $this->assertSession()->waitForElementVisible('css', $css);
    $this->assertNotNull($element, $message);

    return $element;
  }

  /**
   * Clicks an element once it is visible.
   *
   * The native click is deliberately used rather than a synthetic JavaScript
   * click: Drupal's Ajax framework binds many button and summary elements to
   * mousedown, so element.click() would not reliably trigger the request.
   *
   * @param string $css
   *   The CSS selector of the element to click.
   */
  protected function clickElement(string $css): void {
    $this->waitForVisible($css, "Element never became visible: $css")->click();
  }

  /**
   * Waits for in-flight AJAX requests to settle.
   *
   * Every AJAX wait in this hierarchy goes through here so the timeout can be
   * adjusted in one place if slower environments need it.
   */
  protected function waitForAjax(): void {
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Selects an option by its exact value and waits for any AJAX to settle.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   The select element.
   * @param string $value
   *   The option value to select.
   */
  protected function selectOption(NodeElement $select, string $value): void {
    $this->getSession()->getPage()->selectFieldOption(
      (string) $select->getAttribute('name'),
      $value,
    );
    $this->waitForAjax();
  }

  /**
   * Selects the first option in a select whose value satisfies a matcher.
   *
   * Option values under UI Patterns are plugin and derivative IDs whose exact
   * form depends on the module version, so tests match on a stable fragment
   * rather than hard-coding the full value.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   The select element.
   * @param callable $matcher
   *   Receives the option value and returns TRUE on a match.
   * @param string $message
   *   Failure message used when no option matches.
   *
   * @return string
   *   The selected option value.
   */
  protected function selectOptionMatching(NodeElement $select, callable $matcher, string $message): string {
    foreach ($select->findAll('css', 'option') as $option) {
      $value = (string) $option->getAttribute('value');
      if ($value !== '' && $matcher($value)) {
        $this->selectOption($select, $value);

        return $value;
      }
    }

    $this->fail($message);
  }

}
