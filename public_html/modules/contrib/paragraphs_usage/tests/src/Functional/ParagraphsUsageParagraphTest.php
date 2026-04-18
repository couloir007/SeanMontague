<?php

declare(strict_types=1);

namespace Drupal\Tests\paragraphs_usage\Functional;

/**
 * Test paragraphs usage in Paragraph.
 *
 * @group paragraphs_usage
 */
class ParagraphsUsageParagraphTest extends ParagraphsUsageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loginAsAdmin();
  }

  /**
   * Check if paragraph is used in nested_paragraphs.
   */
  public function testCheckIfUsed(): void {

    $this->addParagraphsType('paragraphs');
    $this->addParagraphsType('nested_paragraphs');

    $this->addParagraphsField('paragraphs', 'nested_paragraphs', 'paragraph');

    $this->drupalGet('admin/structure/paragraphs_type/nested_paragraphs/usage');
    $this->assertSession()->pageTextContains('paragraphs');

  }

  /**
   * Check if paragraph is not used in vocabulary2.
   */
  public function testCheckIfNotUsed(): void {

    $this->addParagraphsType('paragraphs');
    $this->addParagraphsType('nested_paragraphs');

    $this->addParagraphsField('paragraphs', 'nested_paragraphs', 'paragraph');

    $this->drupalGet('admin/structure/paragraphs_type/paragraphs/usage');
    $this->assertSession()->pageTextContains('This paragraph is not used in any content type.');
  }

}
