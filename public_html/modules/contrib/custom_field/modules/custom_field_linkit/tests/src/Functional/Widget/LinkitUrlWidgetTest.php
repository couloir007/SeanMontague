<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_linkit\Functional\Widget;

use Drupal\custom_field\Plugin\CustomField\FieldType\LinkTypeInterface;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the linkit_url widget.
 *
 * Requires the contrib 'linkit' module. Assumes the 'default' linkit_profile
 * shipped in linkit's config/optional directory installs automatically,
 * since 'node' is already enabled by the base test class.
 *
 * @group custom_field
 * @group custom_field_linkit
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_linkit')]
#[RunTestsInSeparateProcesses]
class LinkitUrlWidgetTest extends CustomFieldFunctionalTestBase {

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

    $this->createCustomField(
      'field_test',
      [
        'linkit_test' => [
          'name' => 'linkit_test',
          'type' => 'uri',
        ],
      ],
      [
        'linkit_test' => [
          'label' => 'Linkit test',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkTypeInterface::LINK_GENERIC,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'linkit_test' => [
        'type' => 'linkit_url',
        'weight' => 0,
        'label' => 'Linkit test',
        'placeholder' => 'Enter a URL or start typing to search',
        'linkit_profile' => 'default',
      ],
    ]);
  }

  /**
   * Tests the placeholder and linkit_profile widget settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[linkit_test]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals(
      $base . '[placeholder]',
      'Enter a URL or start typing to search'
    );
    $assert->optionExists($base . '[linkit_profile]', 'default');
    $assert->fieldValueEquals($base . '[linkit_profile]', 'default');

    $this->submitForm([
      $base . '[placeholder]' => 'Search for content or paste a link',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][linkit_test][uri]"]',
      'placeholder',
      'Search for content or paste a link'
    );
  }

  /**
   * Tests the uri element carries linkit autocomplete path for the profile.
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
   * Tests that a plain external URL is stored unchanged.
   */
  public function testExternalUrlPassesThrough(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'External url node',
      'field_test[0][linkit_test][uri]' => 'https://www.drupal.org',
    ], 'Save');

    $node = $this->loadNodeByTitle('External url node');
    $this->assertEquals(
      'https://www.drupal.org',
      $node->get('field_test')->linkit_test
    );
  }

  /**
   * Tests that a mailto: input passes through unchanged.
   */
  public function testMailtoPassesThrough(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Mailto node',
      'field_test[0][linkit_test][uri]' => 'mailto:test@example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Mailto node');
    $this->assertEquals(
      'mailto:test@example.com',
      $node->get('field_test')->linkit_test
    );
  }

  /**
   * Tests a path to a real entity resolves to an entity: uri.
   */
  public function testInternalPathResolvesToEntityUri(): void {
    $reference_id = $this->referenceNode->id();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Internal path node',
      'field_test[0][linkit_test][uri]' => "/node/{$reference_id}",
    ], 'Save');

    $node = $this->loadNodeByTitle('Internal path node');
    $this->assertEquals(
      "entity:node/{$reference_id}",
      $node->get('field_test')->linkit_test
    );
  }

  /**
   * Tests self-referencing absolute URLs normalize for root and subdir deploys.
   *
   * Linkit strips scheme+host only; under a subdirectory base path the
   * remainder may not resolve to an entity (linkit won't-fix). Assert both.
   */
  public function testSelfReferencingAbsoluteUrlNormalizes(): void {
    $reference_id = $this->referenceNode->id();
    $absolute_url = $this->baseUrl . "/node/{$reference_id}";
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Self referencing url node',
      'field_test[0][linkit_test][uri]' => $absolute_url,
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
   * Tests a non-entity path stores as internal: (uses /user, not /user/login).
   */
  public function testNonEntityPathStoresInternalScheme(): void {
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Non entity path node',
      'field_test[0][linkit_test][uri]' => '/user',
    ], 'Save');

    $node = $this->loadNodeByTitle('Non entity path node');
    $this->assertEquals(
      'internal:/user',
      $node->get('field_test')->linkit_test
    );
  }

  /**
   * Tests unsafe schemes are rejected by the external protocols constraint.
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
    $value = $node->get('field_test')->linkit_test ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests that a required linkit_url uri is enforced.
   *
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredLinkitUrlValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_linkit_url_required',
      [
        'linkit_url_required' => [
          'name' => 'linkit_url_required',
          'type' => 'uri',
        ],
      ],
      [
        'linkit_url_required' => [
          'label' => 'Linkit url required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'link_type' => LinkTypeInterface::LINK_GENERIC,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_linkit_url_required', [
      'linkit_url_required' => [
        'type' => 'linkit_url',
        'weight' => 0,
        'label' => 'Linkit url required',
        'placeholder' => '',
        'linkit_profile' => 'default',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists(
      'field_linkit_url_required[0][linkit_url_required][uri]'
    );
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required linkit url node',
    ], 'Save');
    $assert->pageTextNotContains('Required linkit url node has been created');

    $uri_field = 'field_linkit_url_required[0][linkit_url_required][uri]';
    $this->submitForm([
      $uri_field => 'https://www.example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required linkit url node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_linkit_url_required')->linkit_url_required
    );
  }

}
