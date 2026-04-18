<?php

declare(strict_types=1);

namespace Drupal\Tests\paragraphs_usage\Functional;

/**
 * Test menu link .
 *
 * @group paragraphs_usage
 */
class ParagraphsUsageAdminToolbarTest extends ParagraphsUsageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'admin_toolbar_tools',
    'field_ui',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loginAsAdmin(['access administration pages', 'access toolbar']);
  }

  /**
   * Check if paragraph type as menu link.
   */
  public function testMenuLinkExist(): void {
    $this->addParagraphsType('test_paragraphs');
    $this->drupalGet('/admin/structure/paragraphs_type');
    $this->assertSession()->linkByHrefExists('/admin/structure/paragraphs_type/test_paragraphs/usage');
  }

}
