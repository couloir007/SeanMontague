<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the email widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class EmailWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'email_test' => [
          'name' => 'email_test',
          'type' => 'email',
        ],
        'email_as_string' => [
          'name' => 'email_as_string',
          'type' => 'string',
          'length' => 254,
        ],
      ],
      [
        'email_test' => [
          'label' => 'Email test',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
        'email_as_string' => [
          'label' => 'Email as string',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'email_test' => [
        'type' => 'email',
        'weight' => 0,
        'label' => 'Email test',
        'size' => 60,
        'placeholder' => '',
      ],
      'email_as_string' => [
        'type' => 'email',
        'weight' => 1,
        'label' => 'Email as string',
        'size' => 60,
        'placeholder' => '',
      ],
    ]);
  }

  /**
   * Tests the size and placeholder widget settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[email_test]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[size]', '60');
    $assert->fieldValueEquals($base . '[placeholder]', '');

    $this->submitForm([
      $base . '[size]' => 40,
      $base . '[placeholder]' => 'name@example.com',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $field = $assert->fieldExists('field_test[0][email_test]');
    $this->assertEquals('40', $field->getAttribute('size'));
    $this->assertEquals(
      'name@example.com',
      $field->getAttribute('placeholder')
    );
  }

  /**
   * Tests that the field renders as a native email input.
   */
  public function testEmailElementType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][email_test]"]',
      'type',
      'email'
    );
  }

  /**
   * Tests that a valid email address persists through save and reload.
   */
  public function testEmailValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Email node',
      'field_test[0][email_test]' => 'person@example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Email node');
    $this->assertEquals(
      'person@example.com',
      $node->get('field_test')->email_test
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][email_test]',
      'person@example.com'
    );
  }

  /**
   * Tests that an empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty email node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty email node');
    $value = $node->get('field_test')->email_test ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a value over 254 characters is rejected.
   *
   * Not asserting specific message text: a local part this long likely
   * also fails the email format validation on #type => 'email' itself
   * (see testMalformedEmailRejectedOnEmailType()), so which validator's
   * message actually appears isn't pinned down here - only that the
   * oversized value is rejected one way or another.
   */
  public function testMaxLengthConstraintRejectsLongValue(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $long_local_part = str_repeat('a', 250);
    $too_long = "{$long_local_part}@example.com";

    $this->submitForm([
      'title[0][value]' => 'Too long email node',
      'field_test[0][email_test]' => $too_long,
    ], 'Save');

    $assert->pageTextNotContains('Too long email node has been created');
  }

  /**
   * Tests that a malformed value is rejected.
   *
   * The rejection comes from Drupal core's #type => 'email' render
   * element itself (its own #element_validate against email.validator),
   * not from anything EmailType declares - see
   * testEmailWidgetOnStringType() for the corollary: this applies
   * identically on a string-typed subfield too, since it's tied to the
   * widget's rendered element type, not the underlying data type.
   */
  public function testMalformedEmailRejectedOnEmailType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Malformed email node',
      'field_test[0][email_test]' => 'not-an-email',
    ], 'Save');

    $assert->pageTextNotContains('Malformed email node has been created');
  }

  /**
   * Tests that EmailWidget works on a string-typed subfield.
   *
   * The field_types includes 'string' alongside 'email', so the same native
   * email input can back a plain string subfield. This does not relax
   * format validation - see testMalformedEmailRejectedOnEmailType()'s
   * docblock - so this only proves a well-formed value round-trips.
   */
  public function testEmailWidgetOnStringType(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'String type node',
      'field_test[0][email_as_string]' => 'person@example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('String type node');
    $this->assertEquals(
      'person@example.com',
      $node->get('field_test')->email_as_string
    );
  }

  /**
   * Tests that a required email is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredEmailValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_email_required',
      [
        'email_required' => [
          'name' => 'email_required',
          'type' => 'email',
        ],
      ],
      [
        'email_required' => [
          'label' => 'Email required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_email_required', [
      'email_required' => [
        'type' => 'email',
        'weight' => 0,
        'label' => 'Email required',
        'size' => 60,
        'placeholder' => '',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_email_required[0][email_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required email node',
    ], 'Save');
    $assert->pageTextNotContains('Required email node has been created');

    $this->submitForm([
      'field_email_required[0][email_required]' => 'person@example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required email node');
    $this->assertEquals(
      'person@example.com',
      $node->get('field_email_required')->email_required
    );
  }

}
