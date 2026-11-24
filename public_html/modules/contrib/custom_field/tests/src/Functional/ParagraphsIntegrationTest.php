<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use Drupal\Tests\custom_field\Traits\ParagraphsIntegrationTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Custom field subfields keep their extra properties inside paragraphs.
 *
 * Paragraphs keeps one paragraph entity in widget state and extracts the
 * submitted values onto it during both the validation and the submit pass, so
 * a field item is set twice per request. Every subfield type whose data type
 * writes extra properties into sibling columns is exercised here.
 *
 * No JavaScript: the viewfield display element only renders once target_id has
 * a value, so the scenarios submit the view first and the display second. The
 * AJAX flows are covered in the FunctionalJavascript counterpart.
 *
 * @group custom_field
 * @group custom_field_viewfield
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_viewfield')]
#[RunTestsInSeparateProcesses]
class ParagraphsIntegrationTest extends CustomFieldFunctionalTestBase {

  use ParagraphsIntegrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'entity_reference_revisions',
    'paragraphs',
    'file',
    'image',
    'custom_field_viewfield',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpParagraphsIntegration();
  }

  /**
   * Adds a card paragraph to the open node form.
   */
  protected function addCard(): void {
    $this->submitForm([], 'Add Card');
  }

  /**
   * Uploads the fixture image into a paragraph delta.
   *
   * @param int $delta
   *   The paragraph delta.
   */
  protected function uploadImage(int $delta): void {
    $this->submitForm([$this->cardImageUpload($delta) => $this->fixtureImagePath()], 'Upload');
    $this->assertSession()->fieldExists($this->cardElement($delta, 'img', '[alt]'));
  }

  /**
   * Returns a full set of submitted values for one paragraph delta.
   *
   * @param int $delta
   *   The paragraph delta.
   * @param array<string, string> $values
   *   Overrides keyed by a short name.
   *
   * @return array<string, string>
   *   Form values keyed by element name.
   */
  protected function cardValues(int $delta, array $values = []): array {
    $values += [
      'txt' => 'Text ' . $delta,
      'uri' => 'https://example.com/' . $delta,
      'link_title' => 'Link ' . $delta,
      'target' => '_blank',
      'dt_date' => '2026-08-15',
      'dt_time' => '09:00:00',
      'timezone' => 'America/New_York',
      'dr_start' => '2026-08-15T09:00',
      'dr_end' => '2026-08-16T17:00',
      'tr_start' => '09:00',
      'tr_end' => '17:00',
      'alt' => 'Alt ' . $delta,
      'img_title' => 'Title ' . $delta,
      'view' => 'view_one',
      'arguments' => 'a/b',
      'items' => '5',
    ];

    return [
      $this->cardElement($delta, 'txt') => $values['txt'],
      $this->cardElement($delta, 'lnk', '[uri]') => $values['uri'],
      $this->cardElement($delta, 'lnk', '[title]') => $values['link_title'],
      $this->cardElement($delta, 'lnk', '[options][attributes][target]') => $values['target'],
      $this->cardElement($delta, 'dt', '[value][date]') => $values['dt_date'],
      $this->cardElement($delta, 'dt', '[value][time]') => $values['dt_time'],
      $this->cardElement($delta, 'dt', '[timezone]') => $values['timezone'],
      $this->cardElement($delta, 'dr', '[value][date]') => $values['dr_start'],
      $this->cardElement($delta, 'dr', '[end_value][date]') => $values['dr_end'],
      $this->cardElement($delta, 'tr', '[value]') => $values['tr_start'],
      $this->cardElement($delta, 'tr', '[end_value]') => $values['tr_end'],
      $this->cardElement($delta, 'img', '[alt]') => $values['alt'],
      $this->cardElement($delta, 'img', '[title]') => $values['img_title'],
      $this->cardElement($delta, 'vf', '[target_id]') => $values['view'],
      $this->cardElement($delta, 'vf', '[view_options][arguments]') => $values['arguments'],
      $this->cardElement($delta, 'vf', '[view_options][items_to_display]') => $values['items'],
    ];
  }

  /**
   * A single paragraph keeps every subfield's extra properties.
   */
  public function testSingleParagraphKeepsAllExtraProperties(): void {
    $assert = $this->assertSession();

    $this->drupalGet('node/add/page');
    $this->addCard();
    $this->uploadImage(0);

    $this->submitForm(
      ['title[0][value]' => 'One card'] + $this->cardValues(0),
      'Save'
    );

    // The display only renders once a view is stored, so set it on re-edit.
    $node = $this->loadNodeByTitle('One card');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldExists($this->cardElement(0, 'vf', '[display_id]'));
    $this->submitForm([$this->cardElement(0, 'vf', '[display_id]') => 'page_1'], 'Save');

    $this->assertCardCount((int) $node->id(), 1);
    $this->assertStoredCard((int) $node->id(), 0, [
      'txt' => 'Text 0',
      'lnk' => 'https://example.com/0',
      'lnk__title' => 'Link 0',
      'dt__timezone' => 'America/New_York',
      'img__alt' => 'Alt 0',
      'img__title' => 'Title 0',
      'vf' => 'view_one',
      'vf__display' => 'page_1',
      'vf__arguments' => 'a/b',
    ]);

    $item = $this->reloadCardItem((int) $node->id(), 0);
    $this->assertSame(['target' => '_blank'], $item->lnk__options['attributes'] ?? NULL);
    $this->assertSame(5, (int) $item->vf__items, 'Viewfield items stored.');
    $this->assertNotEmpty($item->dr__end, 'Date range end stored.');
    $this->assertSame('61200', $item->tr__end, 'Time range end stored as seconds since midnight.');
    $this->assertNotEmpty($item->img__width, 'Image width stored.');
    $this->assertNotEmpty($item->img__height, 'Image height stored.');

    // The re-edit form offers the stored values as defaults.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals($this->cardElement(0, 'lnk', '[title]'), 'Link 0');
    $assert->fieldValueEquals($this->cardElement(0, 'img', '[alt]'), 'Alt 0');
    $assert->fieldValueEquals($this->cardElement(0, 'vf', '[target_id]'), 'view_one');
    $assert->fieldValueEquals($this->cardElement(0, 'vf', '[display_id]'), 'page_1');
  }

  /**
   * Two paragraphs keep distinct values for every subfield.
   */
  public function testTwoParagraphsKeepDistinctValues(): void {
    $assert = $this->assertSession();

    $this->drupalGet('node/add/page');
    $this->addCard();
    $this->addCard();
    $this->uploadImage(0);
    $this->uploadImage(1);

    $edit = ['title[0][value]' => 'Two cards']
      + $this->cardValues(0)
      + $this->cardValues(1, ['view' => 'view_two', 'target' => '_self']);
    $this->submitForm($edit, 'Save');

    $node = $this->loadNodeByTitle('Two cards');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      $this->cardElement(0, 'vf', '[display_id]') => 'page_1',
      $this->cardElement(1, 'vf', '[display_id]') => 'default',
    ], 'Save');

    $this->assertCardCount((int) $node->id(), 2);
    $this->assertStoredCard((int) $node->id(), 0, [
      'txt' => 'Text 0',
      'lnk__title' => 'Link 0',
      'img__alt' => 'Alt 0',
      'vf' => 'view_one',
      'vf__display' => 'page_1',
    ]);
    $this->assertStoredCard((int) $node->id(), 1, [
      'txt' => 'Text 1',
      'lnk__title' => 'Link 1',
      'img__alt' => 'Alt 1',
      'vf' => 'view_two',
      'vf__display' => 'default',
    ]);

    $first = $this->reloadCardItem((int) $node->id(), 0);
    $second = $this->reloadCardItem((int) $node->id(), 1);
    $this->assertSame(['target' => '_blank'], $first->lnk__options['attributes'] ?? NULL);
    $this->assertSame(['target' => '_self'], $second->lnk__options['attributes'] ?? NULL);
    $assert->statusCodeEquals(200);
  }

  /**
   * Editing an existing paragraph can change only the extra properties.
   */
  public function testEditingParagraphChangesOnlyExtraProperties(): void {
    $assert = $this->assertSession();

    $this->drupalGet('node/add/page');
    $this->addCard();
    $this->uploadImage(0);
    $this->submitForm(
      ['title[0][value]' => 'Edit card'] + $this->cardValues(0, ['view' => 'view_two']),
      'Save'
    );

    $node = $this->loadNodeByTitle('Edit card');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([$this->cardElement(0, 'vf', '[display_id]') => 'page_1'], 'Save');
    $this->assertCardCount((int) $node->id(), 1);
    $this->assertStoredCard((int) $node->id(), 0, ['vf__display' => 'page_1']);

    // Now change ONLY extra properties: the display, the link text, the alt.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals($this->cardElement(0, 'vf', '[display_id]'), 'page_1');
    $this->submitForm([
      $this->cardElement(0, 'vf', '[display_id]') => 'default',
      $this->cardElement(0, 'lnk', '[title]') => 'Renamed link',
      $this->cardElement(0, 'img', '[alt]') => 'Renamed alt',
    ], 'Save');

    $this->assertStoredCard((int) $node->id(), 0, [
      // Main values untouched.
      'txt' => 'Text 0',
      'lnk' => 'https://example.com/0',
      'vf' => 'view_two',
      // Extra properties updated.
      'vf__display' => 'default',
      'lnk__title' => 'Renamed link',
      'img__alt' => 'Renamed alt',
    ]);
  }

  /**
   * An empty required viewfield display is rejected and stores nothing.
   */
  public function testEmptyRequiredViewfieldDisplayIsRejected(): void {
    $assert = $this->assertSession();

    $this->drupalGet('node/add/page');
    $this->addCard();
    $this->uploadImage(0);
    $this->submitForm(
      ['title[0][value]' => 'Empty display'] + $this->cardValues(0),
      'Save'
    );

    $node = $this->loadNodeByTitle('Empty display');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldExists($this->cardElement(0, 'vf', '[display_id]'));

    $this->submitForm([$this->cardElement(0, 'vf', '[display_id]') => ''], 'Save');

    $assert->pageTextContains('Display field is required.');
    $this->assertCardCount((int) $node->id(), 1);
    // The rejected submit stored nothing: the view is still there and the
    // display column is genuinely present and NULL, not merely absent.
    $this->assertStoredCard((int) $node->id(), 0, ['vf' => 'view_one']);
    $values = $this->reloadCardItem((int) $node->id(), 0)->getValue();
    $this->assertArrayHasKey('vf__display', $values);
    $this->assertNull($values['vf__display']);
  }

}
