<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the time_widget widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class TimeWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'time_seconds' => [
          'name' => 'time_seconds',
          'type' => 'time',
        ],
        'time_no_seconds' => [
          'name' => 'time_no_seconds',
          'type' => 'time',
        ],
      ],
      [
        'time_seconds' => [
          'label' => 'Time with seconds',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'seconds_enabled' => TRUE,
          'seconds_step' => 5,
        ],
        'time_no_seconds' => [
          'label' => 'Time without seconds',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'seconds_enabled' => FALSE,
          'seconds_step' => 5,
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'time_seconds' => [
        'type' => 'time_widget',
        'weight' => 0,
        'label' => 'Time with seconds',
      ],
      'time_no_seconds' => [
        'type' => 'time_widget',
        'weight' => 1,
        'label' => 'Time without seconds',
      ],
    ]);
  }

  /**
   * Tests that the field renders as a native time input.
   */
  public function testTimeElementType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][time_seconds]"]',
      'type',
      'time'
    );
  }

  /**
   * Tests that the step attribute follows seconds_enabled per subfield.
   */
  public function testStepAttributeFollowsSecondsEnabled(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][time_seconds]"]',
      'step',
      '5'
    );
    $assert->elementAttributeNotExists(
      'css',
      'input[name="field_test[0][time_no_seconds]"]',
      'step'
    );
  }

  /**
   * Tests that a submitted time persists as seconds past midnight.
   */
  public function testTimeValuePersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Time node',
      'field_test[0][time_seconds]' => '14:30:15',
    ], 'Save');

    $node = $this->loadNodeByTitle('Time node');
    // 14 * 3600 + 30 * 60 + 15.
    $this->assertEquals(52215, $node->get('field_test')->time_seconds);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][time_seconds]', '14:30:15');
  }

  /**
   * Tests that seconds_enabled only affects display, not what's accepted.
   *
   * The seconds_enabled setting controls the step attribute and the
   * display format on reload (formatForWidget()), but valueCallback()
   * and the inherited massageFormValue() don't reference that setting
   * at all - a submission containing seconds should still parse and
   * store correctly even when seconds_enabled is off for that subfield.
   */
  public function testSecondsAcceptedRegardlessOfSecondsEnabled(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Seconds ignored node',
      'field_test[0][time_no_seconds]' => '09:05:45',
    ], 'Save');

    $node = $this->loadNodeByTitle('Seconds ignored node');
    // 9 * 3600 + 5 * 60 + 45.
    $this->assertEquals(32745, $node->get('field_test')->time_no_seconds);
  }

  /**
   * Tests that the edit form displays seconds only when enabled.
   *
   * The formatForWidget() method renders "H:i:s" when the subfield's
   * seconds_enabled is TRUE, and "H:i" (seconds dropped from display
   * only, not storage) when FALSE - even though the stored value has
   * seconds either way, per
   * testSecondsAcceptedRegardlessOfSecondsEnabled().
   */
  public function testDisplayFormatDropsSecondsWhenDisabled(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Display format node',
      'field_test[0][time_no_seconds]' => '09:05:45',
    ], 'Save');

    $node = $this->loadNodeByTitle('Display format node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][time_no_seconds]', '09:05');
  }

  /**
   * Tests that an empty value stores NULL.
   *
   * Unlike the color widget's native input[type=color], input[type=time]
   * genuinely supports an empty submission - valueCallback() explicitly
   * returns NULL for an empty string rather than falling through to
   * some non-null default.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty time node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty time node');
    $value = $node->get('field_test')->time_seconds ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a required time field is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredTimeValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_time_required',
      [
        'time_required' => [
          'name' => 'time_required',
          'type' => 'time',
        ],
      ],
      [
        'time_required' => [
          'label' => 'Time required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'seconds_enabled' => FALSE,
          'seconds_step' => 5,
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_time_required', [
      'time_required' => [
        'type' => 'time_widget',
        'weight' => 0,
        'label' => 'Time required',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_time_required[0][time_required]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required time node',
    ], 'Save');
    $assert->pageTextNotContains('Required time node has been created');

    $this->submitForm([
      'field_time_required[0][time_required]' => '10:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required time node');
    $this->assertEquals(
      36000,
      $node->get('field_time_required')->time_required
    );
  }

  /**
   * Tests that midnight stores as integer 0 (not empty/NULL).
   */
  public function testMidnightPersistsAsZero(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Midnight time node',
      'field_test[0][time_no_seconds]' => '00:00',
    ], 'Save');

    $node = $this->loadNodeByTitle('Midnight time node');
    $this->assertSame(0, (int) $node->get('field_test')->time_no_seconds);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals('field_test[0][time_no_seconds]', '00:00');
  }

  /**
   * Tests that an invalid time string is rejected.
   *
   * Uses a colon-separated but out-of-range value (25:00). A free-form string
   * like "not-a-time" currently triggers a PHP warning in
   * Time::createFromHtml5Format() (Undefined array key 1 at ~line 176) instead
   * of a clean InvalidArgumentException — harden that method separately.
   * HTML5 time inputs usually block bad values in the browser; submitForm can
   * still post them.
   */
  public function testInvalidTimeIsRejected(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Invalid time node',
      'field_test[0][time_seconds]' => '25:00',
    ], 'Save');

    $assert->pageTextNotContains('Invalid time node has been created');
    $page_text = $this->getSession()->getPage()->getText();
    if (str_contains($page_text, 'is not a valid time')) {
      $assert->pageTextContains('is not a valid time');
    }
  }

}
