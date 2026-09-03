<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Widget;

use Drupal\Core\Form\FormState;
use Drupal\custom_field\Plugin\CustomField\FieldWidget\SelectOrOtherWidget;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldWidgetKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the select_or_other widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldWidget\SelectOrOtherWidget
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(SelectOrOtherWidget::class)]
class SelectOrOtherWidgetTest extends CustomFieldWidgetKernelTestBase {

  /**
   * Tests default settings.
   */
  public function testDefaultSettings(): void {
    $defaults = SelectOrOtherWidget::defaultSettings();

    $this->assertSame('list', $defaults['select_element_type']);
    $this->assertSame('Other', $defaults['other_field_label']);
    $this->assertSame('', $defaults['other_placeholder']);
    $this->assertSame('', $defaults['other_option']);
    // From ListWidgetBase / parent chain.
    $this->assertArrayHasKey('label', $defaults);
    $this->assertArrayHasKey('empty_option', $defaults);
  }

  /**
   * Tests widgetSettingsForm().
   */
  public function testWidgetSettingsForm(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'select_element_type' => 'buttons',
      'other_field_label' => 'Something else',
      'other_placeholder' => 'Enter custom value',
      'other_option' => 'Other…',
      'label' => 'Choice',
    ]);

    $form_state = new FormState();
    $form = $widget->widgetSettingsForm($form_state, $subfield);

    $this->assertArrayHasKey('select_element_type', $form);
    $this->assertArrayHasKey('list', $form['select_element_type']['#options']);
    $this->assertArrayHasKey('buttons', $form['select_element_type']['#options']);
    $this->assertEquals('buttons', $form['select_element_type']['#default_value']);

    $this->assertArrayHasKey('other_field_label', $form);
    $this->assertArrayHasKey('other_placeholder', $form);
    $this->assertArrayHasKey('other_option', $form);
    $this->assertEquals('Something else', $form['other_field_label']['#default_value']);
    $this->assertEquals('Enter custom value', $form['other_placeholder']['#default_value']);
    $this->assertEquals('Other…', $form['other_option']['#default_value']);
  }

  /**
   * Tests widgetSettingsForm() defaults to 'list' when unset.
   */
  public function testWidgetSettingsFormDefaultsToList(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
        ],
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['choice'];

    // No 'select_element_type' passed in settings.
    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'label' => 'Choice',
    ]);

    $form_state = new FormState();
    $form = $widget->widgetSettingsForm($form_state, $subfield);

    $this->assertEquals('list', $form['select_element_type']['#default_value']);
  }

  /**
   * Tests the widget() method with a value from the options list.
   */
  public function testWidgetWithOptionValue(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'other_field_label' => 'Other',
      'other_placeholder' => '',
      'other_option' => 'Other',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'apple'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    // No 'select_element_type' setting supplied, should default to the select
    // variant.
    $this->assertEquals('custom_field_select_or_other_select', $element['#type']);
    $this->assertEquals('apple', $element['#default_value']);
    $this->assertEquals('Choice', $element['#title']);
    $this->assertEquals('Other', $element['#other_field_label']);
    $this->assertEquals('Other', $element['#other_option']);
    // Value is in the options list, so #other_options should not be populated.
    $this->assertEmpty($element['#other_options']);
  }

  /**
   * Tests the widget() method when the stored value is not in the options.
   */
  public function testWidgetWithOtherValue(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'other_field_label' => 'Other',
      'other_placeholder' => 'Custom…',
      'other_option' => 'Other',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'mango'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertEquals('custom_field_select_or_other_select', $element['#type']);
    $this->assertEquals('mango', $element['#default_value']);
    // Value is not in options, so it should be exposed as the "other" option.
    $this->assertEquals('mango', $element['#other_options']);
    $this->assertEquals('Custom…', $element['#other_placeholder']);
  }

  /**
   * Tests widget() method renders radios when select_element_type is 'buttons'.
   */
  public function testWidgetButtonsType(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'select_element_type' => 'buttons',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'apple'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertEquals('custom_field_select_or_other_radios', $element['#type']);
    $this->assertEquals('apple', $element['#default_value']);
    $this->assertArrayHasKey('apple', $element['#options']);
    $this->assertArrayHasKey('banana', $element['#options']);
  }

  /**
   * Tests the empty option is prepended when nothing is required.
   *
   * #required is `!on_field_config_form && $is_required &&
   * $field_settings['required']` (see CustomFieldWidgetBase::widget()), so
   * both the top-level field and the subfield setting must be TRUE for the
   * element to be required.
   */
  public function testWidgetButtonsTypeNotRequiredHasEmptyOption(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'required' => FALSE,
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $field->setRequired(FALSE);
    $field->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'select_element_type' => 'buttons',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'apple'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertFalse($element['#required']);
    $this->assertArrayHasKey('', $element['#options']);
    $this->assertSame('', array_key_first($element['#options']));
  }

  /**
   * Tests the empty option is not prepended when everything is required.
   */
  public function testWidgetButtonsTypeRequiredHasNoEmptyOption(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'required' => TRUE,
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $field->setRequired(TRUE);
    $field->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'select_element_type' => 'buttons',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'apple'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertTrue($element['#required']);
    $this->assertArrayNotHasKey('', $element['#options']);
  }

  /**
   * Tests the field-level required flag alone does not require the element.
   *
   * The subfield's own 'required' setting must also be on.
   */
  public function testWidgetButtonsTypeFieldRequiredSubfieldNotRequiredHasEmptyOption(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'required' => FALSE,
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    // Field itself is required, but the subfield's own setting is not.
    $field->setRequired(TRUE);
    $field->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'select_element_type' => 'buttons',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'apple'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertFalse($element['#required']);
    $this->assertArrayHasKey('', $element['#options']);
  }

  /**
   * Tests #required is forced FALSE on the field config form.
   *
   * Regardless of the required settings, this is what allows the Default
   * value widget to always show an empty option, which is the scenario
   * that originally surfaced the core #3180011 workaround in
   * SelectOrOtherSelect::addEmptyOption().
   */
  public function testWidgetButtonsTypeOnFieldConfigFormAlwaysHasEmptyOption(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'required' => TRUE,
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $field->setRequired(TRUE);
    $field->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'select_element_type' => 'buttons',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'apple'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $form_state->setBuildInfo(['base_form_id' => 'field_config_form'] + $form_state->getBuildInfo());

    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    // Even though both field- and subfield-level 'required' are TRUE, the
    // field_config_form context forces #required to FALSE.
    $this->assertFalse($element['#required']);
    $this->assertArrayHasKey('', $element['#options']);
  }

  /**
   * Tests "other" detection still works once the empty key is prepended.
   */
  public function testWidgetButtonsTypeWithOtherValue(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'required' => FALSE,
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
          ['key' => 'banana', 'label' => 'Banana'],
        ],
      ],
    ]);
    $field->setRequired(FALSE);
    $field->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $subfield = $this->getCustomFieldItems($field)['choice'];

    $widget = $this->getWidget($subfield, 'choice', 'select_or_other', [
      'select_element_type' => 'buttons',
      'other_placeholder' => 'Custom…',
      'label' => 'Choice',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['choice' => 'mango'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertEquals('custom_field_select_or_other_radios', $element['#type']);
    $this->assertEquals('mango', $element['#other_options']);
    // Confirm the prepended empty option didn't interfere with detecting
    // that 'mango' is not among the real allowed values.
    $this->assertArrayHasKey('', $element['#options']);
    $this->assertArrayNotHasKey('mango', $element['#options']);
  }

  /**
   * Tests number-specific element attributes for integer and float.
   *
   * @dataProvider providerNumericTypes
   */
  public function testWidgetNumericInput(string $type, array $column_extras, mixed $stored_value, string $expected_step): void {
    $column = [
      'name' => 'amount',
      'type' => $type,
    ] + $column_extras;

    $field = $this->createCustomField('field_test', [
      'amount' => $column,
    ], [
      'amount' => [
        'allowed_values' => [
          ['key' => '1', 'label' => 'One'],
          ['key' => '2', 'label' => 'Two'],
        ],
        'min' => 0,
        'max' => 100,
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['amount'];

    $widget = $this->getWidget($subfield, 'amount', 'select_or_other', [
      'label' => 'Amount',
    ]);

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['amount' => $stored_value],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertEquals('custom_field_select_or_other_select', $element['#type']);
    $this->assertEquals('number', $element['#input_type']);
    $this->assertEquals($expected_step, $element['#step']);
    $this->assertEquals(0, $element['#min']);
    $this->assertEquals(100, $element['#max']);
  }

  /**
   * Data provider for testWidgetNumericInput().
   *
   * @return array
   *   Test cases keyed by scenario name.
   */
  public static function providerNumericTypes(): array {
    return [
      'integer' => [
        'integer',
        ['size' => 'normal', 'unsigned' => FALSE],
        1,
        '1',
      ],
      'float' => [
        'float',
        ['size' => 'normal', 'unsigned' => FALSE],
        1.5,
        'any',
      ],
    ];
  }

  /**
   * Tests massageFormValue().
   */
  public function testMassageFormValue(): void {
    $field = $this->createCustomField('field_test', [
      'choice' => [
        'name' => 'choice',
        'type' => 'string',
        'length' => 255,
      ],
    ], [
      'choice' => [
        'allowed_values' => [
          ['key' => 'apple', 'label' => 'Apple'],
        ],
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['choice'];
    $widget = $this->getWidget($subfield, 'choice', 'select_or_other');

    $column = ['name' => 'choice', 'type' => 'string'];

    // Selected a normal option.
    $this->assertSame('apple', $widget->massageFormValue([
      'select' => 'apple',
      'other' => '',
    ], $column));

    // Selected "other" with a custom value.
    $this->assertSame('mango', $widget->massageFormValue([
      'select' => 'select_or_other',
      'other' => 'mango',
    ], $column));

    // Selected "other" but left the textfield empty → NULL.
    $this->assertNull($widget->massageFormValue([
      'select' => 'select_or_other',
      'other' => '',
    ], $column));

    // Non-array input → NULL.
    $this->assertNull($widget->massageFormValue('raw-string', $column));
    $this->assertNull($widget->massageFormValue(NULL, $column));
  }

}
