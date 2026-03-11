<?php

declare(strict_types=1);

namespace Drupal\Tests\msk_devel\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org devel generate content.
 *
 * @group schemadotorg
 */
class SchemaDotOrgDevelGeneralTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'schemadotorg_devel'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test Schema.org devel generate content.
   */
  public function testGenerateContent(): void {
    global $base_path;

    $assert = $this->assertSession();

    $this->createSchemaEntity('node', 'WebPage');

    // Login as user who can't generate content.
    $user = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($user);

    // Check that users without 'generate schemadotorg content' permission.
    // can't generate content.
    $this->drupalGet('node/add/page', ['query' => ['schemadotorg_devel_generate' => 'test']]);
    $assert->fieldValueEquals('title[0][value]', '');

    // Get the 'Add content' page.
    $this->drupalGet('node/add/page');

    // Check that the title is empty.
    $assert->fieldValueEquals('title[0][value]', '');

    // Check that add and generate content tasks do not exist.
    $assert->linkNotExists('Add content');
    $assert->linkNotExists('Generate content');

    // Login as user who can generate content.
    $user = $this->drupalCreateUser(['create page content', 'generate schemadotorg content']);
    $this->drupalLogin($user);

    // Get the 'Add content' page.
    $this->drupalGet('node/add/page');

    // Check that the title is empty.
    $assert->fieldValueEquals('title[0][value]', '');

    // Check that add and generate content tasks do exist.
    $assert->linkExists('Add content');
    $assert->linkExists('Generate content');

    // Check that the 'Add content' amd 'Generate content' link exist.
    $assert->responseContains('<a href="' . $base_path . 'node/add/page" data-drupal-link-system-path="node/add/page">Add content</a>');
    $assert->responseContains('<a href="' . $base_path . 'node/add/page?schemadotorg_devel_generate=test" data-drupal-link-query="{&quot;schemadotorg_devel_generate&quot;:&quot;test&quot;}" data-drupal-link-system-path="node/add/page">Generate content</a>');

    $this->clickLink('Generate content');

    // Check that the title is NOT empty.
    $assert->fieldValueNotEquals('title[0][value]', '');
  }

}
