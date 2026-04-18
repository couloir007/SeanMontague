<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_field_parts\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org allowed formats settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgFieldPartsSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_field_parts'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ])->save();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Field Parts settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_field_parts.settings', '/admin/config/schemadotorg/settings/properties');
  }

}
