<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Widget;

use Drupal\Core\Form\FormState;
use Drupal\custom_field\Plugin\CustomField\FieldWidget\TextWidget;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldWidgetKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the text widget for string subfields.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldWidget\TextWidget
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(TextWidget::class)]
class TextWidgetTest extends CustomFieldWidgetKernelTestBase {

  /**
   * Tests default settings.
   */
  public function testDefaultSettings(): void {
    $defaults = TextWidget::defaultSettings();

    $this->assertSame(60, $defaults['size']);
    $this->assertSame('', $defaults['placeholder']);
    $this->assertSame('', $defaults['maxlength']);
    $this->assertFalse($defaults['maxlength_js']);
    $this->assertArrayHasKey('label', $defaults);
  }

  /**
   * Tests widgetSettingsForm().
   */
  public function testWidgetSettingsForm(): void {
    $field = $this->createCustomField(
      'field_test',
      [
        'title' => [
          'name' => 'title',
          'type' => 'string',
          'length' => 255,
        ],
      ],
    );
    $subfield = $this->getCustomFieldItems($field)['title'];

    $widget = $this->getWidget($subfield, 'title', 'text', [
      'size' => 40,
      'placeholder' => 'Type here',
      'maxlength' => 100,
      'maxlength_js' => FALSE,
      'label' => 'My Label',
    ]);

    $form_state = new FormState();
    $form = $widget->widgetSettingsForm($form_state, $subfield);

    $this->assertArrayHasKey('label', $form);
    $this->assertArrayHasKey('size', $form);
    $this->assertArrayHasKey('placeholder', $form);
    $this->assertArrayHasKey('maxlength', $form);
    $this->assertEquals(40, $form['size']['#default_value']);
    $this->assertEquals('Type here', $form['placeholder']['#default_value']);
    $this->assertEquals(100, $form['maxlength']['#default_value']);
  }

  /**
   * Tests the widget() method.
   */
  public function testWidget(): void {
    $field = $this->createCustomField('field_test', [
      'title' => [
        'name' => 'title',
        'type' => 'string',
        'length' => 255,
      ],
    ]);
    $subfield = $this->getCustomFieldItems($field)['title'];

    $widget = $this->getWidget($subfield, 'title', 'text', [
      'size' => 40,
      'placeholder' => 'Enter title',
      'maxlength' => 120,
      'maxlength_js' => FALSE,
      'label' => 'Title',
    ]);

    // Create via entity so property definitions and values are set up
    // correctly.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'field_test' => [
        ['title' => 'Sample value'],
      ],
    ]);
    $items = $node->get('field_test');

    $form = [];
    $form_state = new FormState();
    $element = $widget->widget($items, 0, [], $form, $form_state, $subfield);

    $this->assertEquals('textfield', $element['#type']);
    $this->assertEquals('Sample value', $element['#default_value']);
    $this->assertEquals(40, $element['#size']);
    $this->assertEquals('Enter title', $element['#placeholder']);
    $this->assertEquals(120, $element['#maxlength']);
    $this->assertEquals('Title', $element['#title']);
  }

}
