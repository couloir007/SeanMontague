<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the integer widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class IntegerWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'int_basic' => [
          'name' => 'int_basic',
          'type' => 'integer',
        ],
        'int_unsigned_no_min' => [
          'name' => 'int_unsigned_no_min',
          'type' => 'integer',
          'unsigned' => TRUE,
        ],
        'int_unsigned_neg_min' => [
          'name' => 'int_unsigned_neg_min',
          'type' => 'integer',
          'unsigned' => TRUE,
        ],
        'int_unsigned_valid_min' => [
          'name' => 'int_unsigned_valid_min',
          'type' => 'integer',
          'unsigned' => TRUE,
        ],
        'int_range' => [
          'name' => 'int_range',
          'type' => 'integer',
        ],
        'int_prefix_suffix' => [
          'name' => 'int_prefix_suffix',
          'type' => 'integer',
        ],
      ],
      [
        'int_basic' => [
          'label' => 'Integer basic',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
        'int_unsigned_no_min' => [
          'label' => 'Unsigned no min',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
        'int_unsigned_neg_min' => [
          'label' => 'Unsigned negative min',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => -5,
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
        'int_unsigned_valid_min' => [
          'label' => 'Unsigned valid min',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => 10,
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
        'int_range' => [
          'label' => 'Integer range',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => 1,
          'max' => 100,
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
        'int_prefix_suffix' => [
          'label' => 'Prefix suffix',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => 'unit|units',
          'suffix' => 'item|items',
          'allowed_values' => [],
        ],
      ],
    );

    $widget_defaults = ['type' => 'integer', 'placeholder' => ''];
    $this->setFormDisplay('field_test', [
      'int_basic' => [
        'weight' => 0,
        'label' => 'Integer basic',
      ] + $widget_defaults,
      'int_unsigned_no_min' => [
        'weight' => 1,
        'label' => 'Unsigned no min',
      ] + $widget_defaults,
      'int_unsigned_neg_min' => [
        'weight' => 2,
        'label' => 'Unsigned negative min',
      ] + $widget_defaults,
      'int_unsigned_valid_min' => [
        'weight' => 3,
        'label' => 'Unsigned valid min',
      ] + $widget_defaults,
      'int_range' => [
        'weight' => 4,
        'label' => 'Integer range',
      ] + $widget_defaults,
      'int_prefix_suffix' => [
        'weight' => 5,
        'label' => 'Prefix suffix',
      ] + $widget_defaults,
    ]);
  }

  /**
   * Tests the placeholder widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[int_basic][placeholder]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->fieldValueEquals($base, '');

    $this->submitForm([
      $base => 'Enter a whole number',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][int_basic]"]',
      'placeholder',
      'Enter a whole number'
    );
  }

  /**
   * Tests that the field renders as a native number input.
   */
  public function testNumberElementType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][int_basic]');
    $this->assertEquals('number', $field->getAttribute('type'));
    $this->assertEquals('any', $field->getAttribute('step'));
  }

  /**
   * Tests that explicit min/max field settings render as attributes.
   */
  public function testMinMaxAttributesRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][int_range]');
    $this->assertEquals('1', $field->getAttribute('min'));
    $this->assertEquals('100', $field->getAttribute('max'));
  }

  /**
   * Tests that an unsigned field with no min forces #min to 0.
   */
  public function testUnsignedWithNoMinForcesZero(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][int_unsigned_no_min]');
    $this->assertEquals('0', $field->getAttribute('min'));
  }

  /**
   * Tests that an unsigned field with a negative min forces #min to 0.
   */
  public function testUnsignedWithNegativeMinForcesZero(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][int_unsigned_neg_min]');
    $this->assertEquals('0', $field->getAttribute('min'));
  }

  /**
   * Tests that an unsigned field with a valid positive min keeps it.
   *
   * Confirms IntegerWidget's defensive override only kicks in when min
   * is unset or negative - it shouldn't clobber a legitimately
   * configured positive min back down to 0.
   */
  public function testUnsignedWithValidMinIsPreserved(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][int_unsigned_valid_min]');
    $this->assertEquals('10', $field->getAttribute('min'));
  }

  /**
   * Tests that the prefix/suffix pipe segment is always the last one.
   *
   * NumberWidgetBase calls array_pop() on the pipe-separated
   * prefix/suffix, unconditionally taking the last segment regardless
   * of the field's current value. This matches Drupal core's own
   * NumberWidget (Drupal\Core\Field\Plugin\Field\FieldWidget\
   * NumberWidget), which does the identical thing - confirmed by
   * comparing the two side by side. Not a bug: without JS, there's no
   * way to update the displayed segment live as someone types a new
   * value, so switching it server-side based on a value that's about
   * to change anyway would just relocate the staleness problem rather
   * than solve it. Always showing one fixed, predictable segment is
   * the deliberate choice, matching core's own long-standing
   * convention for this exact tradeoff.
   */
  public function testPrefixSuffixAlwaysUsesLastPipeSegment(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->pageTextContains('units');
    $assert->pageTextNotContains('unit ');
    $assert->pageTextContains('items');

    $this->submitForm([
      'title[0][value]' => 'Prefix suffix node',
      'field_test[0][int_prefix_suffix]' => '1',
    ], 'Save');

    $node = $this->loadNodeByTitle('Prefix suffix node');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Still plural even though the stored value is exactly 1.
    $assert->pageTextContains('units');
    $assert->pageTextContains('items');
  }

  /**
   * Tests that a submitted value persists correctly.
   *
   * Uses assertEquals() rather than assertSame(): IntegerWidget::
   * massageFormValue() returns the submitted value as-is without an
   * explicit (int) cast, and confirmed via an actual test run, that
   * value genuinely stays a PHP string ('42') all the way through to
   * the magic property accessor - it isn't coerced to a real int by
   * the 'integer' typed data property the way might be assumed. Worth
   * knowing if anything downstream does strict type-checking on this
   * value (e.g. JSON:API would emit "42" as a JSON string, not a
   * number), though not something this test tries to fix.
   */
  public function testValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Integer node',
      'field_test[0][int_basic]' => '42',
    ], 'Save');

    $node = $this->loadNodeByTitle('Integer node');
    $this->assertEquals(42, $node->get('field_test')->int_basic);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][int_basic]', '42');
  }

  /**
   * Tests that a signed integer field accepts and persists negative values.
   *
   * Int_basic has no min and is not unsigned, so negatives are in range.
   */
  public function testNegativeValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Negative integer node',
      'field_test[0][int_basic]' => '-7',
    ], 'Save');

    $node = $this->loadNodeByTitle('Negative integer node');
    $this->assertEquals(-7, $node->get('field_test')->int_basic);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][int_basic]', '-7');
  }

  /**
   * Tests that an empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty integer node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty integer node');
    $value = $node->get('field_test')->int_basic ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a value outside the configured range is rejected.
   *
   * Not asserting specific message text: the widget sets #min/#max
   * directly on the number element, so Drupal core's generic Form API
   * range validation may reject this before NumericTypeBase's own
   * Range constraint message ever gets a chance to fire - same pattern
   * seen with the text/telephone widgets' #maxlength.
   */
  public function testRangeConstraintRejectsOutOfBoundsValue(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Out of range node',
      'field_test[0][int_range]' => '500',
    ], 'Save');

    $assert->pageTextNotContains('Out of range node has been created');
  }

  /**
   * Tests that a required integer field is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredIntegerValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_int_required',
      [
        'int_required' => [
          'name' => 'int_required',
          'type' => 'integer',
        ],
      ],
      [
        'int_required' => [
          'label' => 'Integer required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_int_required', [
      'int_required' => [
        'type' => 'integer',
        'weight' => 0,
        'label' => 'Integer required',
        'placeholder' => '',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_int_required[0][int_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required integer node',
    ], 'Save');
    $assert->pageTextNotContains('Required integer node has been created');

    $this->submitForm([
      'field_int_required[0][int_required]' => '7',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required integer node');
    $this->assertEquals(7, $node->get('field_int_required')->int_required);
  }

  /**
   * Tests that a min=0-only config produces the correct range message.
   *
   * NumericTypeBase::getConstraints()'s message-selection now uses an
   * explicit $min !== NULL && $max !== NULL check rather than loose
   * truthiness, so min = 0 (a natural choice for an unsigned field) is
   * correctly treated as a genuine bound rather than being read as "no
   * bound set" the way `$min && $max` did. With max left unset here,
   * $max still holds NumericTrait's real, enforced default for the
   * field's storage size (4294967295 for an unsigned 'normal'
   * integer), so the message correctly becomes 'notInRangeMessage'
   * rather than the misleading 'minMessage' an earlier version of this
   * test confirmed. Fixed in both NumberWidgetBase (prefix/suffix) and
   * NumericTypeBase (this message selection).
   *
   * The widget doesn't render a #max HTML attribute in this
   * configuration (only #min, since 'max' isn't set at the
   * field-settings level), so there's no generic Form API max check
   * shadowing the entity-level Range constraint here - unlike the
   * #maxlength situations seen with other widgets, this message
   * assertion reliably reaches the real constraint.
   */
  public function testMinZeroOnlyProducesCorrectMessage(): void {
    $this->createCustomField(
      'field_int_min_zero',
      [
        'int_min_zero' => [
          'name' => 'int_min_zero',
          'type' => 'integer',
          'unsigned' => TRUE,
        ],
      ],
      [
        'int_min_zero' => [
          'label' => 'Min zero only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => 0,
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
      ],
    );

    $this->setFormDisplay('field_int_min_zero', [
      'int_min_zero' => [
        'type' => 'integer',
        'weight' => 0,
        'label' => 'Min zero only',
        'placeholder' => '',
      ],
    ]);

    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Exceeds the real, enforced default max for an unsigned 'normal'
    // integer (4294967295).
    $this->submitForm([
      'title[0][value]' => 'Min zero node',
      'field_int_min_zero[0][int_min_zero]' => '5000000000',
    ], 'Save');

    $assert->pageTextNotContains('Min zero node has been created');
    $assert->pageTextContains('must be between 0 and 4294967295');
  }

  /**
   * Tests that storage size bounds are enforced when field min/max are empty.
   *
   * Size = tiny (signed) uses the schema default range -128..127 via
   * NumericTrait::getDefaultMinValue()/getDefaultMaxValue(). Submitting a
   * value outside that range must be rejected even though no field-level
   * min/max were configured.
   *
   * Message text: NumericTypeBase only attaches a custom notInRangeMessage
   * when field-level min and/or max are set. With both empty, the Range
   * constraint still receives the storage defaults as min/max but uses the
   * constraint plugin's default wording (e.g. "This value should be between
   * -128 and 127."), not the "%name: the value must be between..." string
   * used by testMinZeroOnlyProducesCorrectMessage().
   */
  public function testTinySizeRejectsOutOfBoundsValue(): void {
    $this->createCustomField(
      'field_int_tiny',
      [
        'int_tiny' => [
          'name' => 'int_tiny',
          'type' => 'integer',
          'size' => 'tiny',
          'unsigned' => FALSE,
        ],
      ],
      [
        'int_tiny' => [
          'label' => 'Integer tiny',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '',
          'allowed_values' => [],
        ],
      ],
    );

    $this->setFormDisplay('field_int_tiny', [
      'int_tiny' => [
        'type' => 'integer',
        'weight' => 0,
        'label' => 'Integer tiny',
        'placeholder' => '',
      ],
    ]);

    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Above signed TINYINT max (127).
    $this->submitForm([
      'title[0][value]' => 'Tiny overflow node',
      'field_int_tiny[0][int_tiny]' => '200',
    ], 'Save');

    $assert->pageTextNotContains('Tiny overflow node has been created');
    // Default Range constraint message (no custom notInRangeMessage when
    // field min/max are empty). Assert the storage bounds appear in the
    // error rather than a specific full sentence.
    $assert->pageTextContains('-128');
    $assert->pageTextContains('127');
  }

}
