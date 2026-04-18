<?php

declare(strict_types=1);

namespace Drupal\Tests\paragraphs_usage\Functional;

/**
 * Test paragraphs usage in Content Types.
 *
 * @group paragraphs_usage
 */
class ParagraphsUsageContentTypesTest extends ParagraphsUsageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType([
      'type' => 'article',
      'name' => 'article',
    ]);
    $this->createContentType([
      'type' => 'page',
      'name' => 'page',
    ]);

    $this->loginAsAdmin([
      'administer content types',
      'administer node form display',
      'administer node fields',
      'create article content',
      'create page content',
    ]);
  }

  /**
   * Check if paragraph is used in article content type.
   */
  public function testCheckIfUsed(): void {
    $this->addParagraphsType('test_paragraphs');
    $this->addParagraphsField('article', 'test_paragraphs', 'node');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs/usage');
    $this->assertSession()->pageTextContains('article');

  }

  /**
   * Check if paragraph is not used in page content type.
   */
  public function testCheckIfNotUsed(): void {
    $this->addParagraphsType('test_paragraphs');
    $this->addParagraphsField('article', 'test_paragraphs', 'node');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs/usage');
    $this->assertSession()->pageTextNotContains('page');
  }

  /**
   * Check if paragraph is not used in any content type.
   */
  public function testCheckIsEmpty(): void {
    $this->addParagraphsType('test_paragraphs');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs/usage');
    $this->assertSession()->pageTextContains('This paragraph is not used in any content type.');
  }

  /**
   * Check if paragraph don't exist.
   */
  public function testCheckIfParagraphDontExist(): void {
    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs/usage');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Check if Usage link in exist in paragraph's tabs.
   */
  public function testIfTabExist(): void {
    $this->addParagraphsType('test_paragraphs');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs');
    $this->assertSession()
      ->elementExists('xpath', '//li//a[contains(@href, "/admin/structure/paragraphs_type/test_paragraphs/usage")]');
  }

  /**
   * Check if Usage link in exist in operations.
   */
  public function testIfOperationExist(): void {
    $this->addParagraphsType('test_paragraphs');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs');
    $this->assertSession()
      ->elementExists('xpath', '//li//a[contains(@href, "/admin/structure/paragraphs_type/test_paragraphs/usage")]');
  }

  /**
   * Check if paragraphs are included.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testIfParagraphAreIncluded(): void {
    $this->addParagraphsType('paragraph_1');
    $this->addParagraphsType('paragraph_2');

    $this->addParagraphsField('article', 'paragraphs', 'node');
    $this->setAllowedParagraphsTypes('article', ['paragraph_1', 'paragraph_2'], TRUE, 'paragraphs', FALSE);

    $this->drupalGet('node/add/article');
    $this->assertSession()->buttonExists('Add paragraph_1');
    $this->assertSession()->buttonExists('Add paragraph_2');
  }

  /**
   * Check if paragraphs are excluded.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testIfParagraphAreExcluded(): void {
    $this->addParagraphsType('paragraph_1');
    $this->addParagraphsType('paragraph_2');

    $this->addParagraphsField('article', 'paragraphs', 'node');
    $this->setAllowedParagraphsTypes('article', ['paragraph_1', 'paragraph_2'], TRUE, 'paragraphs', TRUE);

    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('You are not allowed to add any of the Paragraph types.');
  }

}
