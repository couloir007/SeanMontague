<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the checkbox widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class CheckboxWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'checkbox_optional' => [
          'name' => 'checkbox_optional',
          'type' => 'boolean',
        ],
      ],
      [
        'checkbox_optional' => [
          'label' => 'Optional checkbox',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'checkbox_optional' => [
        'type' => 'checkbox',
        'weight' => 0,
        'label' => 'Optional checkbox',
      ],
    ]);
  }

  /**
   * Tests that the checkbox renders unchecked with no existing value.
   */
  public function testDefaultRendersUnchecked(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->checkboxNotChecked('field_test[0][checkbox_optional]');
  }

  /**
   * Tests that a checked value persists as truthy through save and reload.
   */
  public function testCheckedValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Checked node',
      'field_test[0][checkbox_optional]' => TRUE,
    ], 'Save');

    $node = $this->loadNodeByTitle('Checked node');
    $this->assertTrue((bool) $node->get('field_test')->checkbox_optional);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->checkboxChecked('field_test[0][checkbox_optional]');
  }

  /**
   * Tests that an unchecked checkbox stores FALSE, not NULL.
   */
  public function testUncheckedValuePersistsAsFalse(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Unchecked node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Unchecked node');
    $value = $node->get('field_test')->checkbox_optional;
    $this->assertFalse((bool) $value);
    $this->assertNotNull($value);
  }

  /**
   * Tests that a required checkbox follows standard required validation.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredCheckboxValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_checkbox_required',
      [
        'checkbox_required' => [
          'name' => 'checkbox_required',
          'type' => 'boolean',
        ],
      ],
      [
        'checkbox_required' => [
          'label' => 'Required checkbox',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_checkbox_required', [
      'checkbox_required' => [
        'type' => 'checkbox',
        'weight' => 0,
        'label' => 'Required checkbox',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists(
      'field_checkbox_required[0][checkbox_required]'
    );
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Unchecked required node',
    ], 'Save');
    $assert->pageTextNotContains('Unchecked required node has been created');

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Checked required node',
      'field_checkbox_required[0][checkbox_required]' => TRUE,
    ], 'Save');

    $node = $this->loadNodeByTitle('Checked required node');
    $this->assertTrue(
      (bool) $node->get('field_checkbox_required')->checkbox_required
    );
  }

  /**
   * Tests toggling a previously checked value back off on edit.
   */
  public function testTogglingOffOnEditPersists(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Toggle node',
      'field_test[0][checkbox_optional]' => TRUE,
    ], 'Save');

    $node = $this->loadNodeByTitle('Toggle node');
    $this->assertTrue((bool) $node->get('field_test')->checkbox_optional);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'field_test[0][checkbox_optional]' => FALSE,
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertFalse((bool) $node->get('field_test')->checkbox_optional);
  }

}
