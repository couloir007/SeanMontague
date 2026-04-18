<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_identifier\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org identifier settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgIdentifierSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_identifier'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org identifier settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_identifier.settings', '/admin/config/schemadotorg/settings/properties');
  }

}
