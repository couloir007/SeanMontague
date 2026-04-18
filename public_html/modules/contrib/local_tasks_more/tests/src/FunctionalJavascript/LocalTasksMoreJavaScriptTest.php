<?php

declare(strict_types=1);

namespace Drupal\Tests\local_tasks_more\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Local Tasks More JavaScript.
 *
 * @covers local_tasks_more.js
 * @group local_tasks_more
 */
class LocalTasksMoreJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'local_tasks_more_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->placeBlock('local_tasks_block');
  }

  /**
   * Test local tasks more.
   */
  public function testLocalTasksMore(): void {
    $page = $this->getSession()->getPage();

    $this->drupalGet('local_tasks_more_test/01');

    // Check that primary and secondary local task are initialized with only
    // five links visible per level.
    $this->assetLinkVisible('Primary 01');
    $this->assetLinkVisible('Primary 02');
    $this->assetLinkVisible('Primary 03');
    $this->assetLinkVisible('Primary 04');
    $this->assetLinkVisible('Primary 05');
    $this->assetLinkNotVisible('Primary 06');
    $this->assetLinkNotVisible('Primary 07');
    $this->assetLinkNotVisible('Primary 08');
    $this->assetLinkNotVisible('Primary 09');
    $this->assetLinkVisible('Secondary 11');
    $this->assetLinkVisible('Secondary 12');
    $this->assetLinkVisible('Secondary 13');
    $this->assetLinkVisible('Secondary 14');
    $this->assetLinkVisible('Secondary 15');
    $this->assetLinkNotVisible('Secondary 16');
    $this->assetLinkNotVisible('Secondary 17');
    $this->assetLinkNotVisible('Secondary 18');
    $this->assetLinkNotVisible('Secondary 19');

    // Check that primary more links can be toggled visible.
    $page->find('css', '.local-tasks-more-toggle-primary a')->click();
    $this->assetLinkVisible('Primary 06');
    $this->assetLinkVisible('Primary 07');
    $this->assetLinkVisible('Primary 08');
    $this->assetLinkVisible('Primary 09');
    $this->assetLinkNotVisible('Secondary 16');
    $this->assetLinkNotVisible('Secondary 17');
    $this->assetLinkNotVisible('Secondary 18');
    $this->assetLinkNotVisible('Secondary 19');

    // Check that secondary more links can be toggled visible.
    $page->find('css', '.local-tasks-more-toggle-secondary a')->click();
    $this->assetLinkVisible('Primary 06');
    $this->assetLinkVisible('Primary 07');
    $this->assetLinkVisible('Primary 08');
    $this->assetLinkVisible('Primary 09');
    $this->assetLinkVisible('Secondary 16');
    $this->assetLinkVisible('Secondary 17');
    $this->assetLinkVisible('Secondary 18');
    $this->assetLinkVisible('Secondary 19');

    // Check that primary more links can be toggled hidden.
    $page->find('css', '.local-tasks-more-toggle-primary a')->click();
    $this->assetLinkNotVisible('Primary 06');
    $this->assetLinkNotVisible('Primary 07');
    $this->assetLinkNotVisible('Primary 08');
    $this->assetLinkNotVisible('Primary 09');

    // Check that the toggle state is saved in local storage.
    $this->drupalGet('local_tasks_more_test/01');
    $this->assetLinkVisible('Primary 01');
    $this->assetLinkVisible('Primary 02');
    $this->assetLinkVisible('Primary 03');
    $this->assetLinkVisible('Primary 04');
    $this->assetLinkVisible('Primary 05');
    $this->assetLinkNotVisible('Primary 06');
    $this->assetLinkNotVisible('Primary 07');
    $this->assetLinkNotVisible('Primary 08');
    $this->assetLinkNotVisible('Primary 09');
    $this->assetLinkVisible('Secondary 11');
    $this->assetLinkVisible('Secondary 12');
    $this->assetLinkVisible('Secondary 13');
    $this->assetLinkVisible('Secondary 14');
    $this->assetLinkVisible('Secondary 15');
    $this->assetLinkVisible('Secondary 16');
    $this->assetLinkVisible('Secondary 17');
    $this->assetLinkVisible('Secondary 18');
    $this->assetLinkVisible('Secondary 19');
  }

  /**
   * Assert link is visible.
   *
   * @param string $locator
   *   Link id, title, text or image alt.
   */
  protected function assetLinkVisible(string $locator): void {
    $this->assertTrue($this->getSession()->getPage()->findLink($locator)->isVisible());
  }

  /**
   * Assert link is NOT visible.
   *
   * @param string $locator
   *   Link id, title, text or image alt.
   */
  protected function assetLinkNotVisible(string $locator): void {
    $this->assertFalse($this->getSession()->getPage()->findLink($locator)->isVisible());
  }

}
