<?php

declare(strict_types=1);

namespace Drupal\Tests\paragraphs_usage\Functional;

/**
 * Test paragraphs usage on User.
 *
 * @group paragraphs_usage
 */
class ParagraphsUsageUserTest extends ParagraphsUsageTestBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loginAsAdmin();
  }

  /**
   * Check if paragraph is used on user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCheckIfParagraphIsUsed(): void {
    $this->addParagraphsType('test_paragraphs');
    $this->addParagraphsField('user', 'test_paragraphs', 'user');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs/usage');
    $this->assertSession()->pageTextContains('paragraphs');
  }

  /**
   * Check if paragraph isn't used on user.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckIfParagraphIsNotUsed(): void {
    $this->addParagraphsType('paragraphs');
    $this->addParagraphsType('nested_paragraphs');

    $this->addParagraphsField('user', 'nested_paragraphs', 'user');

    $this->drupalGet('admin/structure/paragraphs_type/paragraphs/usage');
    $this->assertSession()->pageTextContains('This paragraph is not used in any content type.');
  }

}
