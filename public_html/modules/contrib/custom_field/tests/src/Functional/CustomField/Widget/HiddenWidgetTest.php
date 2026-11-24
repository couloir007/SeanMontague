<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the hidden widget.
 *
 * The hidden widget renders as Form API #type value: not visible on the
 * form, value preserved from the entity (or set programmatically). Covered
 * for a representative string subfield; the plugin lists many field_types
 * but behavior is the same for all.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class HiddenWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'hidden_string' => [
          'name' => 'hidden_string',
          'type' => 'string',
          'length' => 255,
        ],
      ],
      [
        'hidden_string' => [
          'label' => 'Hidden string',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'hidden_string' => [
        'type' => 'hidden',
        'weight' => 0,
        'label' => 'Hidden string',
      ],
    ]);
  }

  /**
   * Tests the subfield is not rendered as a visible form control.
   */
  public function testFieldNotVisibleOnForm(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldNotExists('field_test[0][hidden_string]');
    $assert->elementNotExists(
      'css',
      'input[name="field_test[0][hidden_string]"]'
    );
    $assert->elementNotExists(
      'css',
      'textarea[name="field_test[0][hidden_string]"]'
    );
  }

  /**
   * Tests a programmatically set value is preserved through form save.
   *
   * Create the node via the API, open the edit form (hidden input only),
   * save without touching the subfield, and confirm the value remains.
   */
  public function testProgrammaticValuePreservedOnEdit(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Hidden value node',
      'field_test' => [
        'hidden_string' => 'set-via-api',
      ],
    ]);
    $node->save();

    $this->assertEquals(
      'set-via-api',
      $node->get('field_test')->hidden_string
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldNotExists('field_test[0][hidden_string]');

    $this->submitForm([
      'title[0][value]' => 'Hidden value node updated',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals(
      'set-via-api',
      $node->get('field_test')->hidden_string
    );
    $this->assertEquals('Hidden value node updated', $node->label());
  }

  /**
   * Tests an empty programmatic value stays empty after form save.
   */
  public function testEmptyValueStaysEmptyOnEdit(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Empty hidden node',
      'field_test' => [],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'Save');

    $node = $this->reloadNode($node->id());
    $value = $node->get('field_test')->hidden_string ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

}
