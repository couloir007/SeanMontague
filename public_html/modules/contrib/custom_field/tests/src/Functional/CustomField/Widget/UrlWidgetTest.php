<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the url widget.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class UrlWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * A node available to reference via entity autocomplete.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referenceNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->referenceNode = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Referenceable page',
    ]);

    $this->createCustomField(
      'field_test',
      [
        'uri_both' => [
          'name' => 'uri_both',
          'type' => 'uri',
        ],
        'uri_internal' => [
          'name' => 'uri_internal',
          'type' => 'uri',
        ],
        'uri_external' => [
          'name' => 'uri_external',
          'type' => 'uri',
        ],
      ],
      [
        'uri_both' => [
          'label' => 'Both link types',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => 17,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
        ],
        'uri_internal' => [
          'label' => 'Internal only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => 1,
          'field_prefix' => 'custom',
          'field_prefix_custom' => 'https://www.custom-example.com',
        ],
        'uri_external' => [
          'label' => 'External only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => 16,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
        ],
      ],
    );

    $widget_defaults = [
      'type' => 'url',
    ];

    $this->setFormDisplay('field_test', [
      'uri_both' => [
        'weight' => 0,
        'label' => 'Both link types',
        'placeholder' => 'Enter a path or URL',
      ] + $widget_defaults,
      'uri_internal' => [
        'weight' => 1,
        'label' => 'Internal only',
        'placeholder' => 'Enter an internal path',
      ] + $widget_defaults,
      'uri_external' => [
        'weight' => 2,
        'label' => 'External only',
        'placeholder' => 'Enter an external URL',
      ] + $widget_defaults,
    ]);
  }

  /**
   * Tests the placeholder widget setting via Manage form display.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $placeholder_path = self::FIELD_PATH . '[uri_external][placeholder]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $assert->statusCodeEquals(200);
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldExists($placeholder_path);
    $assert->fieldValueEquals($placeholder_path, 'Enter an external URL');

    $this->submitForm([
      $placeholder_path => 'https://example.com',
    ], 'field_test_plugin_settings_update');

    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][uri_external][uri]"]',
      'placeholder',
      'https://example.com'
    );
  }

  /**
   * Tests that the rendered element type follows the link_type setting.
   *
   * External-only fields use the native HTML5 url element; internal-only
   * or both must use entity_autocomplete instead, since browser url
   * validation can't accommodate internal paths.
   */
  public function testElementTypeMatchesLinkType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][uri_external][uri]"]',
      'type',
      'url'
    );

    foreach (['uri_both', 'uri_internal'] as $subfield) {
      $field = $assert->fieldExists("field_test[0][{$subfield}][uri]");
      $this->assertNotEquals(
        'url',
        $field->getAttribute('type'),
        "{$subfield} should not render as a native url input."
      );
      $this->assertStringContainsString(
        'form-autocomplete',
        (string) $field->getAttribute('class'),
        "{$subfield} should render as an entity autocomplete field."
      );
    }
  }

  /**
   * Tests create/edit for a field allowing both internal and external links.
   */
  public function testCreateAndEditBothLinkType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $reference_id = $this->referenceNode->id();
    $autocomplete_value = $this->referenceNode->label() . " ({$reference_id})";

    $this->submitForm([
      'title[0][value]' => 'Both link type node',
      'field_test[0][uri_both][uri]' => $autocomplete_value,
    ], 'Save');

    $assert->pageTextContains('Both link type node');

    $node = $this->loadNodeByTitle('Both link type node');
    $this->assertEquals(
      "entity:node/{$reference_id}",
      $node->get('field_test')->uri_both
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][uri_both][uri]',
      $autocomplete_value
    );

    // An external URL should also be accepted on the same field.
    $this->submitForm([
      'field_test[0][uri_both][uri]' => 'https://www.drupal.org',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals(
      'https://www.drupal.org',
      $node->get('field_test')->uri_both
    );
  }

  /**
   * Tests create/edit for an internal-only field, including the field prefix.
   */
  public function testCreateAndEditInternalOnly(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // The custom field_prefix should be visible on the form.
    $assert->pageTextContains('https://www.custom-example.com');

    $reference_id = $this->referenceNode->id();
    $autocomplete_value = $this->referenceNode->label() . " ({$reference_id})";

    $this->submitForm([
      'title[0][value]' => 'Internal only node',
      'field_test[0][uri_internal][uri]' => $autocomplete_value,
    ], 'Save');

    $node = $this->loadNodeByTitle('Internal only node');
    $this->assertEquals(
      "entity:node/{$reference_id}",
      $node->get('field_test')->uri_internal
    );

    // '/user/login' requires an anonymous session, so avoid it here -
    // CustomFieldLinkAccess would reject it for the logged-in admin.
    $internal_path = "/node/{$reference_id}";
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'field_test[0][uri_internal][uri]' => $internal_path,
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals(
      "internal:{$internal_path}",
      $node->get('field_test')->uri_internal
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][uri_internal][uri]',
      $internal_path
    );

    // A manually entered path missing a leading /, ? or # is rejected.
    $this->submitForm([
      'field_test[0][uri_internal][uri]' => ltrim($internal_path, '/'),
    ], 'Save');
    $assert->pageTextContains('Manually entered paths should start with one of '
      . 'the following characters: / ? #');
  }

  /**
   * Tests create/edit for an external-only field.
   */
  public function testCreateAndEditExternalOnly(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'External only node',
      'field_test[0][uri_external][uri]' => 'https://www.example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('External only node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_test')->uri_external
    );

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][uri_external][uri]',
      'https://www.example.com'
    );

    $this->submitForm([
      'field_test[0][uri_external][uri]' => 'https://updated.example.com',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals(
      'https://updated.example.com',
      $node->get('field_test')->uri_external
    );
  }

  /**
   * Tests that an empty uri value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty uri node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty uri node');
    foreach (['uri_both', 'uri_internal', 'uri_external'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests that the 'link data valid for link type' constraint is enforced.
   *
   * The widget itself doesn't restrict input based on link_type - it's the
   * CustomFieldLinkType entity validation constraint that rejects a
   * resolved URI that doesn't match the field's allowed link type.
   */
  public function testLinkTypeConstraintExternalOnly(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Schemeless input becomes an 'internal:' URI regardless of
    // link_type - CustomFieldLinkType rejects it at validation time.
    // flagErrors() then strips the scheme before display.
    $this->submitForm([
      'title[0][value]' => 'Rejected internal path node',
      'field_test[0][uri_external][uri]' => '/node/reject-test',
    ], 'Save');

    $assert->pageTextContains("The path '/node/reject-test' is invalid.");
    $assert->pageTextNotContains(
      'Rejected internal path node has been created'
    );
  }

  /**
   * Tests that an external URL is rejected on an internal-only field.
   */
  public function testLinkTypeConstraintInternalOnly(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Rejected external url node',
      'field_test[0][uri_internal][uri]' => 'https://www.example.com',
    ], 'Save');

    $assert->pageTextContains("The path 'https://www.example.com' is invalid.");
    $assert->pageTextNotContains('Rejected external url node has been created');
  }

  /**
   * Tests that the external protocols constraint rejects unsafe schemes.
   */
  public function testExternalProtocolConstraintRejectsUnsafeScheme(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Rejected protocol node',
      'field_test[0][uri_both][uri]' => 'javascript:alert(1)',
    ], 'Save');

    $assert->pageTextContains("The path 'javascript:alert(1)' is invalid.");
    $assert->pageTextNotContains('Rejected protocol node has been created');
  }

  /**
   * Tests that a required uri is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredUriValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_uri_required',
      [
        'uri_required' => [
          'name' => 'uri_required',
          'type' => 'uri',
        ],
      ],
      [
        'uri_required' => [
          'label' => 'Uri required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'link_type' => 17,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_uri_required', [
      'uri_required' => [
        'type' => 'url',
        'weight' => 0,
        'label' => 'Uri required',
        'placeholder' => '',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_uri_required[0][uri_required][uri]');
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required uri node',
    ], 'Save');
    $assert->pageTextNotContains('Required uri node has been created');

    $this->submitForm([
      'field_uri_required[0][uri_required][uri]' => 'https://www.example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required uri node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_uri_required')->uri_required
    );
  }

}
