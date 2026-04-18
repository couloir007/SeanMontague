<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_block_content_status\Functional;

use Drupal\Tests\schemadotorg_block_content\Functional\SchemaDotOrgBlockContentTest;

/**
 * Tests the functionality of the Schema.org block content status module.
 *
 * @covers schemadotorg_block_content_status_block_view_alter()
 *
 * @group schemadotorg
 */
class SchemaDotOrgBlockContentStatusTest extends SchemaDotOrgBlockContentTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_block_content_status',
  ];

  /**
   * Test Schema.org block_content.
   */
  public function testBlockContent(): void {
    $assert = $this->assertSession();

    // Check that the content block is displayed.
    // @see schemadotorg_block_content_status_block_view_alter()
    $this->drupalGet('<front>');
    $assert->responseContains('<h2 class="visually-hidden">Status message</h2>');
    $assert->responseContains('<div>This is a test</div>');
  }

}
