<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the map_key_value widget.
 *
 * Covers key_label/value_label settings, empty-form Add more presence,
 * edit/persist of programmatically stored pairs, empty storage, and
 * validation (both key and value required; unique keys). AJAX "Add more"
 * create flow is covered in FunctionalJavascript.
 *
 * Form keys for each pair delta use the multivalue "items" container:
 * field_test[0][kv][{delta}][items][key|value].
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class MapKeyValueWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'kv' => [
          'name' => 'kv',
          'type' => 'map',
        ],
      ],
      [
        'kv' => [
          'label' => 'Key value map',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'kv' => [
        'type' => 'map_key_value',
        'weight' => 0,
        'label' => 'Key value map',
        'key_label' => 'Key',
        'value_label' => 'Value',
      ],
    ]);
  }

  /**
   * Tests key_label and value_label widget settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[kv]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[key_label]', 'Key');
    $assert->fieldValueEquals($base . '[value_label]', 'Value');

    $this->submitForm([
      $base . '[key_label]' => 'Attribute',
      $base . '[value_label]' => 'Content',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    // Labels are titles on the pair inputs, so they only appear once a delta
    // exists. Seed a node and assert on the edit form.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Map kv labels node',
      'field_test' => [
        'kv' => [
          ['key' => 'k1', 'value' => 'v1'],
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->pageTextContains('Attribute');
    $assert->pageTextContains('Content');
  }

  /**
   * Tests empty map shows Add more and no pair inputs.
   */
  public function testEmptyFormShowsAddMoreOnly(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Unlimited multivalue with empty default renders no deltas.
    $assert->fieldNotExists('field_test[0][kv][0][items][key]');
    // Add more control should be present (name is parents-based).
    $assert->elementExists('css', 'input[name$="_add_more"][value*="Add"]');
  }

  /**
   * Tests programmatically stored pairs render and persist on edit.
   */
  public function testEditExistingPairsPersist(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Map kv node',
      'field_test' => [
        'kv' => [
          ['key' => 'color', 'value' => 'blue'],
          ['key' => 'size', 'value' => 'large'],
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');

    // Existing deltas should populate the items container fields.
    $assert->fieldValueEquals('field_test[0][kv][0][items][key]', 'color');
    $assert->fieldValueEquals('field_test[0][kv][0][items][value]', 'blue');
    $assert->fieldValueEquals('field_test[0][kv][1][items][key]', 'size');
    $assert->fieldValueEquals('field_test[0][kv][1][items][value]', 'large');

    $this->submitForm([
      'field_test[0][kv][0][items][value]' => 'green',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $pairs = $node->get('field_test')->kv;
    $this->assertIsArray($pairs);
    $this->assertEquals('color', $pairs[0]['key']);
    $this->assertEquals('green', $pairs[0]['value']);
    $this->assertEquals('size', $pairs[1]['key']);
    $this->assertEquals('large', $pairs[1]['value']);
  }

  /**
   * Tests empty map stores NULL or empty array.
   */
  public function testEmptyValueStoresEmpty(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty map kv node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty map kv node');
    $value = $node->get('field_test')->kv ?? NULL;
    $this->assertTrue(
      $value === NULL || $value === [] || $value === '',
      'Empty map should store NULL or empty array.'
    );
  }

  /**
   * Tests both key and value are required when either is filled.
   */
  public function testPartialPairRejected(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Partial pair node',
      'field_test' => [
        'kv' => [
          ['key' => 'only-key', 'value' => 'has-value'],
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'field_test[0][kv][0][items][key]' => 'only-key',
      'field_test[0][kv][0][items][value]' => '',
    ], 'Save');

    $assert->pageTextContains('Both');
    $assert->pageTextNotContains('Partial pair node has been updated');
  }

  /**
   * Tests duplicate keys are rejected.
   */
  public function testDuplicateKeysRejected(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Duplicate key node',
      'field_test' => [
        'kv' => [
          ['key' => 'same', 'value' => 'one'],
          ['key' => 'other', 'value' => 'two'],
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'field_test[0][kv][0][items][key]' => 'same',
      'field_test[0][kv][0][items][value]' => 'one',
      'field_test[0][kv][1][items][key]' => 'SAME',
      'field_test[0][kv][1][items][value]' => 'two',
    ], 'Save');

    $assert->pageTextContains('All keys must be unique');
    $assert->pageTextNotContains('Duplicate key node has been updated');
  }

}
