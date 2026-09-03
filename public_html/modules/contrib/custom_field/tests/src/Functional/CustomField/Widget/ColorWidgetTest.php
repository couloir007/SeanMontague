<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the color widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class ColorWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $field = $this->createCustomField(
      'field_test',
      [
        'color_optional' => [
          'name' => 'color_optional',
          'type' => 'color',
        ],
        'color_required' => [
          'name' => 'color_required',
          'type' => 'color',
        ],
      ],
      [
        'color_optional' => [
          'label' => 'Optional color',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
        'color_required' => [
          'label' => 'Required color',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
        ],
      ],
    );

    // Marks the outer field required so the subfield-level 'required'
    // setting above actually takes effect - see CustomFieldWidgetBase.
    $field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_test', [
      'color_optional' => [
        'type' => 'color',
        'weight' => 0,
        'label' => 'Optional color',
      ],
      'color_required' => [
        'type' => 'color',
        'weight' => 1,
        'label' => 'Required color',
      ],
    ]);
  }

  /**
   * Tests that the color input renders as a native color element.
   *
   * The maxlength isn't a valid HTML attribute for input[type=color], so the
   * widget's '#maxlength' => 7 setting never surfaces as an actual
   * attribute - only 'type' is meaningfully assertable here.
   */
  public function testColorElementAttributes(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][color_optional]"]',
      'type',
      'color'
    );
  }

  /**
   * Tests that a valid hex color value persists through save and reload.
   *
   * A presave hook saves the stored color value uppercase, so a lowercase
   * submission should come back uppercase.
   */
  public function testColorValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Color node',
      'field_test[0][color_optional]' => '#ff5733',
      'field_test[0][color_required]' => '#00ff00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Color node');
    $this->assertEquals('#FF5733', $node->get('field_test')->color_optional);
    $this->assertEquals('#00FF00', $node->get('field_test')->color_required);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][color_optional]', '#FF5733');
  }

  /**
   * Tests that a required color element is genuinely marked #required.
   *
   * Proves the premise behind testOmittedColorDefaultsToBlack(): the
   * "can't be left empty" conclusion only means something if #required
   * is actually TRUE here, not just configured and silently ignored.
   */
  public function testRequiredColorElementIsMarkedRequired(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][color_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );
  }

  /**
   * Tests that an omitted color value defaults to black, not empty.
   *
   * A native input[type=color] can't represent "no value" the way a text
   * field can - per the HTML5 spec, an invalid or missing color value
   * defaults to #000000, and Drupal's color render element applies that
   * default server-side. Omitting the field from a submission therefore
   * stores '#000000', not NULL or an empty string - true even for the
   * genuinely required color_required (see previous test), since there's
   * no empty state for required validation to ever catch here.
   */
  public function testOmittedColorDefaultsToBlack(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Omitted color node',
      'field_test[0][color_required]' => '#00ff00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Omitted color node');
    $this->assertEquals('#000000', $node->get('field_test')->color_optional);
  }

}
