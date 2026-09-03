<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_linkit\Functional\Widget;

use Drupal\custom_field\Plugin\CustomField\FieldType\LinkType;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the linkit widget.
 *
 * Requires linkit; uses the default profile when node is enabled.
 * Entity meta attributes and title autofill from suggestion clicks are
 * JS-only (see FunctionalJavascript).
 *
 * @group custom_field
 * @group custom_field_linkit
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_linkit')]
#[RunTestsInSeparateProcesses]
class LinkitWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'linkit',
    'custom_field_linkit',
  ];

  /**
   * A node available to reference by path.
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

    $enabled_attributes = [
      'id' => FALSE,
      'name' => FALSE,
      'target' => TRUE,
      'rel' => TRUE,
      'class' => TRUE,
      'accesskey' => FALSE,
    ];

    $link_settings = [
      'check_empty' => FALSE,
      'required' => FALSE,
      'description' => '',
      'link_type' => LinkType::LINK_GENERIC,
      'field_prefix' => 'default',
      'field_prefix_custom' => '',
      'title' => DRUPAL_OPTIONAL,
      'enabled_attributes' => $enabled_attributes,
      'widget_default_open' => LinkType::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
    ];

    $this->createCustomField(
      'field_test',
      [
        'linkit_test' => [
          'name' => 'linkit_test',
          'type' => 'link',
        ],
        'linkit_autofill' => [
          'name' => 'linkit_autofill',
          'type' => 'link',
        ],
      ],
      [
        'linkit_test' => ['label' => 'Linkit test'] + $link_settings,
        'linkit_autofill' => [
          'label' => 'Linkit autofill test',
        ] + $link_settings,
      ],
    );

    $this->setFormDisplay('field_test', [
      'linkit_test' => [
        'type' => 'linkit',
        'weight' => 0,
        'label' => 'Linkit test',
        'placeholder_url' => 'Enter a URL or start typing to search',
        'placeholder_title' => 'Enter link text',
        'maxlength' => 100,
        'maxlength_js' => FALSE,
        'linkit_profile' => 'default',
        'linkit_auto_link_text' => FALSE,
      ],
      'linkit_autofill' => [
        'type' => 'linkit',
        'weight' => 1,
        'label' => 'Linkit autofill test',
        'placeholder_url' => 'Enter a URL or start typing to search',
        'placeholder_title' => 'Enter link text',
        'maxlength' => 100,
        'maxlength_js' => FALSE,
        'linkit_profile' => 'default',
        'linkit_auto_link_text' => TRUE,
      ],
    ]);
  }

  /**
   * Tests the widget settings form, including linkit-specific settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[linkit_test]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals(
      $base . '[placeholder_url]',
      'Enter a URL or start typing to search'
    );
    $assert->fieldValueEquals($base . '[maxlength]', '100');
    $assert->optionExists($base . '[linkit_profile]', 'default');
    $assert->fieldValueEquals($base . '[linkit_profile]', 'default');
    $assert->checkboxNotChecked($base . '[linkit_auto_link_text]');

    $this->submitForm([
      $base . '[linkit_auto_link_text]' => TRUE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeExists(
      'css',
      'input[name="field_test[0][linkit_test][title]"]',
      'data-linkit-widget-title-autofill-enabled'
    );
  }

  /**
   * Tests that the autofill data attribute follows the setting per subfield.
   */
  public function testTitleAutofillAttributeFollowsSetting(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementAttributeNotExists(
      'css',
      'input[name="field_test[0][linkit_test][title]"]',
      'data-linkit-widget-title-autofill-enabled'
    );
    $assert->elementAttributeExists(
      'css',
      'input[name="field_test[0][linkit_autofill][title]"]',
      'data-linkit-widget-title-autofill-enabled'
    );
  }

  /**
   * Tests that the uri element carries the linkit autocomplete route.
   */
  public function testAutocompleteAttributePresent(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists('field_test[0][linkit_test][uri]');
    $path = $field->getAttribute('data-autocomplete-path');

    $this->assertNotEmpty($path, 'Autocomplete path attribute is set.');
    $this->assertStringContainsString('default', (string) $path);
  }

  /**
   * Tests that a plain path to a real entity resolves to an entity: uri.
   */
  public function testInternalPathResolvesToEntityUri(): void {
    $reference_id = $this->referenceNode->id();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Internal path node',
      'field_test[0][linkit_test][uri]' => "/node/{$reference_id}",
      'field_test[0][linkit_test][title]' => 'Reference link',
    ], 'Save');

    $node = $this->loadNodeByTitle('Internal path node');
    $this->assertEquals(
      "entity:node/{$reference_id}",
      $node->get('field_test')->linkit_test
    );
    $this->assertEquals(
      'Reference link',
      $node->get('field_test')->linkit_test__title
    );
  }

  /**
   * Tests self-referencing absolute URLs normalize for root and subdir deploys.
   *
   * Same Linkit base-path limitation as LinkitUrlWidgetTest.
   */
  public function testSelfReferencingAbsoluteUrlNormalizes(): void {
    $reference_id = $this->referenceNode->id();
    $absolute_url = $this->baseUrl . "/node/{$reference_id}";
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Self referencing url node',
      'field_test[0][linkit_test][uri]' => $absolute_url,
      'field_test[0][linkit_test][title]' => 'Self reference',
    ], 'Save');

    $node = $this->loadNodeByTitle('Self referencing url node');
    $base_path = (string) parse_url($this->baseUrl, PHP_URL_PATH);
    $expected = $base_path === ''
      ? "entity:node/{$reference_id}"
      : "internal:{$base_path}/node/{$reference_id}";

    $this->assertEquals(
      $expected,
      $node->get('field_test')->linkit_test
    );
  }

  /**
   * Tests external URL + title round trip and title-without-URL validation.
   */
  public function testExternalUrlAndTitleRoundTrip(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Title only node',
      'field_test[0][linkit_test][title]' => 'Orphaned title',
    ], 'Save');
    $assert->pageTextContains(
      'The URL field is required when the Link text field is specified.'
    );
    $assert->pageTextNotContains('Title only node has been created');

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'External url node',
      'field_test[0][linkit_test][uri]' => 'https://www.drupal.org',
      'field_test[0][linkit_test][title]' => 'Drupal',
    ], 'Save');

    $node = $this->loadNodeByTitle('External url node');
    $this->assertEquals(
      'https://www.drupal.org',
      $node->get('field_test')->linkit_test
    );
    $this->assertEquals(
      'Drupal',
      $node->get('field_test')->linkit_test__title
    );
  }

  /**
   * Tests attributes merge; meta keys present but empty without JS selection.
   */
  public function testAttributesAndLinkitMetaMerge(): void {
    $class_field = 'field_test[0][linkit_test][options][attributes][class]';
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Attributes node',
      'field_test[0][linkit_test][uri]' => 'https://www.example.com',
      'field_test[0][linkit_test][title]' => 'Example site',
      'field_test[0][linkit_test][options][attributes][target]' => '_blank',
      'field_test[0][linkit_test][options][attributes][rel]' => 'nofollow',
      $class_field => 'btn btn-primary',
    ], 'Save');

    $node = $this->loadNodeByTitle('Attributes node');
    $options = $node->get('field_test')->linkit_test__options;

    $this->assertEquals('_blank', $options['attributes']['target']);
    $this->assertEquals('nofollow', $options['attributes']['rel']);
    $this->assertEquals(
      ['btn', 'btn-primary'],
      $options['attributes']['class']
    );

    $meta_keys = [
      'href',
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-substitution',
    ];
    foreach ($meta_keys as $meta_key) {
      $this->assertArrayHasKey($meta_key, $options);
      $this->assertSame('', $options[$meta_key]);
    }
  }

  /**
   * Tests that the external protocols constraint still applies.
   */
  public function testExternalProtocolConstraintRejectsUnsafeScheme(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Rejected protocol node',
      'field_test[0][linkit_test][uri]' => 'javascript:alert(1)',
    ], 'Save');

    $assert->pageTextContains("The path 'javascript:alert(1)' is invalid.");
    $assert->pageTextNotContains(
      'Rejected protocol node has been created'
    );
  }

  /**
   * Tests that an empty submission stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty linkit node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty linkit node');
    foreach (['linkit_test', 'linkit_autofill'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests that a required linkit link uri is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredLinkitUriValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_linkit_required',
      [
        'linkit_uri_required' => [
          'name' => 'linkit_uri_required',
          'type' => 'link',
        ],
      ],
      [
        'linkit_uri_required' => [
          'label' => 'Linkit uri required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'link_type' => LinkType::LINK_GENERIC,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
          'title' => DRUPAL_OPTIONAL,
          'enabled_attributes' => [
            'id' => FALSE,
            'name' => FALSE,
            'target' => FALSE,
            'rel' => FALSE,
            'class' => FALSE,
            'accesskey' => FALSE,
          ],
          'widget_default_open' => LinkType::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_linkit_required', [
      'linkit_uri_required' => [
        'type' => 'linkit',
        'weight' => 0,
        'label' => 'Linkit uri required',
        'placeholder_url' => '',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
        'linkit_profile' => 'default',
        'linkit_auto_link_text' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists(
      'field_linkit_required[0][linkit_uri_required][uri]'
    );
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required linkit uri node',
    ], 'Save');
    $assert->pageTextNotContains('Required linkit uri node has been created');

    $uri_field = 'field_linkit_required[0][linkit_uri_required][uri]';
    $this->submitForm([
      $uri_field => 'https://www.example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required linkit uri node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_linkit_required')->linkit_uri_required
    );
  }

}
