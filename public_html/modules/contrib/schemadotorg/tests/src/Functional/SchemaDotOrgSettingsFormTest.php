<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Functional;

/**
 * Tests the functionality of the Schema.org settings form and element.
 *
 * @covers \Drupal\schemadotorg\Element\SchemaDotOrgSettings
 * @covers \Drupal\schemadotorg\Form\SchemaDotOrgSettingsFormBase
 *
 * @group schemadotorg
 */
class SchemaDotOrgSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_settings_form_test'];

  /**
   * Test Schema.org webpage settings form.
   */
  public function testSettingsForm(): void {
    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
    $this->assertSaveSettingsConfigForm('schemadotorg.settings', '/admin/config/schemadotorg/settings/types');
    $this->assertSaveSettingsConfigForm('schemadotorg.settings', '/admin/config/schemadotorg/settings/properties');
    $this->assertSaveSettingsConfigForm('schemadotorg.names', '/admin/config/schemadotorg/settings/names');
  }

  /**
   * Test simple form.
   */
  public function testSimpleForm(): void {
    $assert = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('schemadotorg-settings-simple-form-test');

    // Check expected values when submitting the form via text format.
    $assert->fieldValueEquals('schemadotorg_settings_form_test[yaml]', 'title: YAML');
    $this->submitForm([], 'Save configuration');
    $expected_data = <<<EOT
textfield: some_value
checkbox: true
checkboxes:
  - one
  - two
  - three
indexed:
  - one
  - two
  - three
indexed_grouped:
  A:
    - one
    - two
    - three
  B:
    - four
    - five
    - six
indexed_grouped_named:
  A:
    label: 'Group A'
    items:
      - one
      - two
      - three
  B:
    label: 'Group B'
    items:
      - four
      - five
      - six
associative:
  one: One
  two: Two
  three: Three
associative_grouped:
  A:
    one: One
    two: Two
    three: Three
  B:
    four: Four
    five: Five
    six: Six
associative_grouped_named:
  A:
    label: 'Group A'
    items:
      one: One
      two: Two
      three: Three
  B:
    label: 'Group B'
    items:
      four: Four
      five: Five
      six: Six
links:
  -
    title: Yahoo!!!
    uri: 'https://yahoo.com'
  -
    title: Google
    uri: 'https://google.com'
links_grouped:
  A:
    -
      title: Yahoo!!!
      uri: 'https://yahoo.com'
  B:
    -
      title: Google
      uri: 'https://google.com'
associative_advanced:
  title: Title
  required: true
  height: 100
  width: 100
yaml:
  title: YAML
yaml_raw: 'title: YAML raw'
json_raw: |-
  {
    "name": "value"
  }
EOT;
    $assert->responseContains($expected_data);

    // Check browse token and Schema.org links.
    $assert->linkExists('Browse available tokens.');
    $assert->linkExists('Browse Schema.org types.');

    // Check re-apply settings.
    $this->drupalGet('schemadotorg-settings-simple-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[apply]' => TRUE], 'Save configuration');
    $assert->responseContains('schemadotorg_settings_form_test have been re-applied to all existing Schema.org mappings.');

    // Check YAML validation.
    $this->drupalGet('schemadotorg-settings-simple-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[indexed]' => '"not: valid yaml'], 'Save configuration');
    $assert->responseContains('Error message');

    // Check YAML raw validation.
    $this->drupalGet('schemadotorg-settings-simple-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[yaml_raw]' => '"not: valid yaml'], 'Save configuration');
    $assert->responseContains('Error message');

    // Check JSON raw validation.
    $this->drupalGet('schemadotorg-settings-simple-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[json_raw]' => '"not: valid json'], 'Save configuration');
    $assert->responseContains('Error message');

    // Check configuration Schema.org validation.
    $this->drupalGet('schemadotorg-settings-simple-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[indexed]' => 'not: [valid schema]'], 'Save configuration');
    $assert->responseContains('indexed field is invalid.');
    $assert->responseContains('The configuration property indexed.not.0 doesn&#039;t exist.');

    // Check hiding the actions if they are the only visible element on the form.
    $this->drupalGet('schemadotorg-settings-simple-form-test');
    $assert->buttonExists('Save configuration');
    $this->drupalGet('/schemadotorg-settings-simple-form-test', ['query' => ['no_elements' => 1]]);
    $assert->buttonNotExists('Save configuration');

    // Assert saving a settings form does not alter the expected values.
    $this->assertSaveSettingsConfigForm('schemadotorg_settings_form_test.settings', '/schemadotorg-settings-simple-form-test');
  }

  /**
   * Test custom form.
   */
  public function testCustomForm(): void {
    $assert = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('schemadotorg-settings-custom-form-test');

    // Check expected values when submitting the form via text format.
    $assert->fieldValueEquals('schemadotorg_settings_form_test[yaml]', 'title: YAML');
    $this->submitForm([], 'Save configuration');
    $expected_data = <<<EOT
indexed:
  - one
  - two
  - three
indexed_grouped:
  A:
    - one
    - two
    - three
  B:
    - four
    - five
    - six
indexed_grouped_named:
  A:
    label: 'Group A'
    items:
      - one
      - two
      - three
  B:
    label: 'Group B'
    items:
      - four
      - five
      - six
associative:
  one: One
  two: Two
  three: Three
associative_grouped:
  A:
    one: One
    two: Two
    three: Three
  B:
    four: Four
    five: Five
    six: Six
associative_grouped_named:
  A:
    label: 'Group A'
    items:
      one: One
      two: Two
      three: Three
  B:
    label: 'Group B'
    items:
      four: Four
      five: Five
      six: Six
links_grouped:
  A:
    -
      title: Yahoo!!!
      uri: 'https://yahoo.com'
  B:
    -
      title: Google
      uri: 'https://google.com'
associative_advanced:
  title: Title
  required: true
  height: 100
  width: 100
yaml:
  title: YAML
yaml_raw: 'title: YAML raw'
json_raw: |-
  {
    "name": "value"
  }
EOT;
    $assert->responseContains($expected_data);

    // Check YAML validation.
    $this->drupalGet('schemadotorg-settings-custom-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[indexed]' => '"not: valid yaml'], 'Save configuration');
    $assert->responseContains('Error message');

    // Check YAML raw validation.
    $this->drupalGet('schemadotorg-settings-custom-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[yaml_raw]' => '"not: valid yaml'], 'Save configuration');
    $assert->responseContains('Error message');

    // Check JSON raw validation.
    $this->drupalGet('schemadotorg-settings-custom-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[json_raw]' => '"not: valid json'], 'Save configuration');
    $assert->responseContains('Error message');

    // Check configuration Schema.org validation.
    $this->drupalGet('schemadotorg-settings-custom-form-test');
    $this->submitForm(['schemadotorg_settings_form_test[indexed]' => 'not: [valid schema]'], 'Save configuration');
    $assert->responseContains('indexed field is invalid.');
    $assert->responseContains('The configuration property indexed.not.0 doesn&#039;t exist.');
  }

}
