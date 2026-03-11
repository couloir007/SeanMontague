<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_block_content_status\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org block content settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgBlockContentStatusSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_block_content_status'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Block content settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_block_content_status.settings', '/admin/config/schemadotorg/settings/types');
  }

}
