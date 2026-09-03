<?php

namespace Drupal\Tests\custom_field\FunctionalJavascript\FieldWidget;

use Drupal\custom_field\Time;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for testing custom field widget plugins.
 *
 * Test cases provided in this class apply to all widget plugins.
 */
abstract class CustomFieldWidgetTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_test',
    'user',
    'system',
    'field',
    'field_ui',
    'text',
    'node',
    'path',
  ];

  /**
   * The field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The custom field generate data service.
   *
   * @var \Drupal\custom_field\Service\GenerateDataInterface
   */
  protected $customFieldDataGenerator;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The custom fields on the test entity bundle.
   *
   * @var array|\Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected array $fields = [];

  /**
   * URL to field's storage configuration form.
   *
   * @var string
   */
  protected string $fieldStorageConfigUrl;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->customFieldDataGenerator = $this->container->get('custom_field.generate_data');
    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
    $this->fieldStorageConfigUrl = '/admin/structure/types/manage/custom_field_entity_test/fields/node.custom_field_entity_test.field_test';

    $this->fields = $this->entityFieldManager
      ->getFieldDefinitions('node', 'custom_field_entity_test');

    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
  }

  /**
   * Tests the custom field widgets for current form display.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testWidgets(): void {
    $this->drupalGet('/node/add/custom_field_entity_test');
    $generator = $this->customFieldDataGenerator;

    // Fill out the single cardinality field.
    $form_values = $generator->generateSampleFormData($this->fields['field_test']);
    $this->submitFormWithNativeInputs($form_values, 'Save');
    $this->assertSession()->pageTextContains('Custom Field Entity Test Test has been created.');
    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');
    $this->assertSession()->waitForElementVisible('css', '#edit-field-test-0');
    // Test the generated form values.
    $this->processGeneratedFormValues($form_values);

    // Fill out the multiple cardinality field.
    $form_values = $generator->generateSampleFormData(
      $this->fields['field_test_multiple'],
      [0, 1, 2]
    );
    $this->submitFormWithNativeInputs($form_values, 'Save');
    $this->assertSession()->pageTextContains('Custom Field Entity Test Test has been updated.');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');
    $this->assertSession()->waitForElementVisible('css', '#edit-field-test-0');
    // Test the generated form values.
    $this->processGeneratedFormValues($form_values);

    // Fill out the unlimited cardinality field (and add another several times).
    $page = $this->getSession()->getPage();
    for ($i = 0; $i < 4; ++$i) {
      $page->pressButton('Add another test item');
      $this->assertSession()->assertWaitOnAjaxRequest();
    }
    $form_values = $generator->generateSampleFormData(
      $this->fields['field_test_unlimited'],
      [0, 1, 2, 3, 4]
    );
    $this->submitFormWithNativeInputs($form_values, 'Save');
    $this->assertSession()->pageTextContains('Custom Field Entity Test Test has been updated.');
    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');
    $this->assertSession()->waitForElementVisible('css', '#edit-field-test-0');
    // Test the generated form values.
    $this->processGeneratedFormValues($form_values);

    // Verify elements are not visible now that form has data.
    $this->drupalGet('/admin/structure/types/manage/custom_field_entity_test/fields/node.custom_field_entity_test.field_test');
    // Verify the clone settings field no longer exists.
    $this->assertSession()->elementNotExists('css', '[name="field_storage[subform][settings][clone]"]');
    // Verify the add another button is hidden.
    $this->assertSession()->elementNotExists('css', '#edit-field-storage-subform-settings-actions-add');
  }

  /**
   * Native HTML5 input types where WebDriver's keystroke entry is unreliable.
   *
   * Covers locale-dependent segmented widgets, or types the browser doesn't
   * accept typed keystrokes for at all, so we set them via JS instead of
   * relying on Mink's normal fillField()/submitForm() typing.
   */
  protected const JS_ASSIGNED_INPUT_TYPES = [
    'date',
    'time',
    'datetime-local',
    'month',
    'week',
    'color',
  ];

  /**
   * Submits a form, bypassing keystroke entry for unreliable native widgets.
   *
   * WebDriver can't reliably type into native date/time/color inputs, so
   * those are assigned via JS instead before the rest of the form submits
   * normally.
   *
   * @param string[] $form_values
   *   Form values keyed by selector name.
   * @param string $submit
   *   The label of the submit button to click.
   */
  protected function submitFormWithNativeInputs(array $form_values, string $submit): void {
    $session = $this->getSession();
    $page = $session->getPage();

    foreach ($form_values as $name => $value) {
      $element = $page->find('css', '[name="' . $name . '"]');
      if ($element === NULL) {
        continue;
      }
      $type = $element->getAttribute('type');
      if ($type === NULL || !in_array($type, static::JS_ASSIGNED_INPUT_TYPES, TRUE)) {
        continue;
      }

      $js_value = $this->normalizeValueForNativeInput($type, $value);

      $js = sprintf(
        'var el = document.querySelector(\'[name="%s"]\'); '
        . 'if (el) { el.value = %s; '
        . 'el.dispatchEvent(new Event("input", {bubbles:true})); '
        . 'el.dispatchEvent(new Event("change", {bubbles:true})); }',
        addslashes($name),
        json_encode($js_value)
      );
      $session->executeScript($js);
      unset($form_values[$name]);
    }

    $this->submitForm($form_values, $submit);
  }

  /**
   * Converts a value into the format a native HTML5 input expects.
   *
   * Used for direct .value assignment, without mutating what's used for
   * later assertions against the submitted/expected value.
   *
   * @param string $type
   *   The native input's type attribute (e.g. 'date', 'time', 'color').
   * @param string $value
   *   The value as generated/submitted, in whatever format GenerateData
   *   produced it in.
   *
   * @return string
   *   The value converted to the format expected by the given input type's
   *   .value property. Types other than 'time' are returned unchanged.
   */
  protected function normalizeValueForNativeInput(string $type, string $value): string {
    if ($type === 'time') {
      foreach (['h:iA', 'h:i A', 'H:i:s', 'H:i'] as $format) {
        // The leading '!' resets all unspecified components (date and
        // time) to the Unix epoch before applying the format, instead of
        // PHP's default of filling them in from the current time — which
        // otherwise leaves a random, non-deterministic seconds value on
        // any format (like 'h:iA') that doesn't include seconds.
        $dt = \DateTime::createFromFormat('!' . $format, $value);
        if ($dt !== FALSE) {
          return $dt->format('H:i:s');
        }
      }
    }
    return $value;
  }

  /**
   * Loops through form values and validates their existence.
   *
   * @param string[] $form_values
   *   An array of form values keyed by selector name.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function processGeneratedFormValues(array $form_values): void {
    foreach ($form_values as $subfield => $value) {
      $assert_session = $this->assertSession();
      $page = $this->getSession()->getPage();
      $assert_session->elementExists('css', '[name="' . $subfield . '"]');
      $element = $page->find('css', '[name="' . $subfield . '"]');
      $id = $element->getAttribute('id');
      $field = $assert_session->waitForField($id);
      $this->assertNotNull($field, "Field $subfield was found.");
      $saved_value = $field->getValue();
      if (str_contains($subfield, 'boolean')) {
        // The 0 value in booleans appears to be treated as null, so skip it.
        if ($saved_value) {
          $this->assertTrue($field->isChecked(), 'Field ' . $subfield . ' was not checked.');
        }
        continue;
      }
      if (str_contains($subfield, 'time') && !str_contains($subfield, 'datetime')) {
        $saved_value = Time::createFromHtml5Format($saved_value)
          ->format('h:iA');
      }
      if (str_contains($subfield, 'daterange') && str_contains($subfield, '[date]')) {
        // Native <input type="datetime-local">'s .value getter omits the
        // seconds component whenever seconds is exactly zero, per the HTML
        // spec's value-serialization algorithm — regardless of the step
        // attribute. Strip a trailing ':00' from both sides before comparing
        // so that spec-compliant rounding doesn't register as a mismatch.
        $normalize = static fn (string $v): string => preg_replace('/:00$/', '', $v);
        $this->assertEquals($normalize($value), $normalize($saved_value), "Field $subfield has expected value.");
        continue;
      }
      // Confirm what was submitted matches what was saved.
      $this->assertEquals($value, $saved_value, "Field $subfield has expected value.");
    }
  }

  /**
   * Sets the site timezone to a given timezone.
   *
   * @param string $timezone
   *   The timezone identifier to set.
   */
  protected function setSiteTimezone(string $timezone): void {
    // Set an explicit site timezone, and disallow per-user timezones.
    $this->config('system.date')
      ->set('timezone.user.configurable', 0)
      ->set('timezone.default', $timezone)
      ->save();
  }

}
