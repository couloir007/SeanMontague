<?php

declare(strict_types=1);

namespace Drupal\Tests\paragraphs_usage\Functional;

/**
 * Test paragraphs usage in Taxonomy.
 *
 * @group paragraphs_usage
 */
class ParagraphsUsageTaxonomyTest extends ParagraphsUsageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loginAsAdmin();
  }

  /**
   * Check if paragraph is used in vocabulary.
   */
  public function testCheckIfUsed(): void {

    $this->createVocabulary('vocabulary');
    $this->createTerm('term', 'vocabulary');

    $this->addParagraphsType('test_paragraphs');
    $this->addParagraphsField('vocabulary', 'test_paragraphs', 'taxonomy_term');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs/usage');
    $this->assertSession()->pageTextContains('vocabulary');

  }

  /**
   * Check if paragraph is not used in vocabulary2.
   */
  public function testCheckIfNotUsed(): void {
    $this->createVocabulary('vocabulary1');
    $this->createTerm('term1', 'vocabulary1');

    $this->createVocabulary('vocabulary2');
    $this->createTerm('term2', 'vocabulary2');

    $this->addParagraphsType('test_paragraphs');
    $this->addParagraphsField('vocabulary1', 'test_paragraphs', 'taxonomy_term');

    $this->drupalGet('admin/structure/paragraphs_type/test_paragraphs/usage');
    $this->assertSession()->pageTextNotContains('vocabulary2');
    $this->assertSession()->pageTextNotContains('This paragraph is not used in any content type.');
  }

}
