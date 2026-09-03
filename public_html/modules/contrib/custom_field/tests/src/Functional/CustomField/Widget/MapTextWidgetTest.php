<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the map_text widget.
 *
 * Covers empty-form Add more presence, edit/persist of programmatically
 * stored values, empty storage, and unique-value validation. AJAX "Add more"
 * create flow is covered in FunctionalJavascript.
 *
 * Form keys: field_test[0][mt][{delta}][value].
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class MapTextWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'mt' => [
          'name' => 'mt',
          'type' => 'map_string',
        ],
      ],
      [
        'mt' => [
          'label' => 'Text map',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'mt' => [
        'type' => 'map_text',
        'weight' => 0,
        'label' => 'Text map',
      ],
    ]);
  }

  /**
   * Tests empty map shows Add more and no value inputs.
   */
  public function testEmptyFormShowsAddMoreOnly(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldNotExists('field_test[0][mt][0][value]');
    $assert->elementExists('css', 'input[name$="_add_more"][value*="Add"]');
  }

  /**
   * Tests programmatically stored values render and persist on edit.
   */
  public function testEditExistingValuesPersist(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Map text node',
      'field_test' => [
        // Storage uses a list of strings (short notation for single child).
        'mt' => ['apple', 'banana'],
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');

    $assert->fieldValueEquals('field_test[0][mt][0][value]', 'apple');
    $assert->fieldValueEquals('field_test[0][mt][1][value]', 'banana');

    $this->submitForm([
      'field_test[0][mt][0][value]' => 'cherry',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $values = $node->get('field_test')->mt;
    $this->assertIsArray($values);
    // Filtered values may be keyed by delta.
    $list = array_values($values);
    $this->assertContains('cherry', $list);
    $this->assertContains('banana', $list);
  }

  /**
   * Tests empty map stores NULL or empty array.
   */
  public function testEmptyValueStoresEmpty(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty map text node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty map text node');
    $value = $node->get('field_test')->mt ?? NULL;
    $this->assertTrue(
      $value === NULL || $value === [] || $value === '',
      'Empty map_string should store NULL or empty array.'
    );
  }

  /**
   * Tests duplicate values are rejected.
   */
  public function testDuplicateValuesRejected(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Duplicate map text node',
      'field_test' => [
        'mt' => ['one', 'two'],
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'field_test[0][mt][0][value]' => 'same',
      'field_test[0][mt][1][value]' => 'same',
    ], 'Save');

    $assert->pageTextContains('All values must be unique');
    $assert->pageTextNotContains('Duplicate map text node has been updated');
  }

  /**
   * Tests whitespace-only values are dropped on save.
   */
  public function testWhitespaceOnlyDropped(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Whitespace map text node',
      'field_test' => [
        'mt' => ['keep-me', 'drop-me'],
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'field_test[0][mt][0][value]' => 'keep-me',
      'field_test[0][mt][1][value]' => '   ',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $values = array_values($node->get('field_test')->mt ?? []);
    $this->assertContains('keep-me', $values);
    $this->assertCount(1, $values);
  }

}
