<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\custom_field\Plugin\CustomField\FieldType\LinkType;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the link_default widget.
 *
 * Requires the contrib 'maxlength' module for the maxlength_js coverage.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class LinkWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'maxlength',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $enabled_attributes = [
      'id' => FALSE,
      'name' => FALSE,
      'target' => TRUE,
      'rel' => TRUE,
      'class' => TRUE,
      'accesskey' => FALSE,
    ];

    $this->createCustomField(
      'field_test',
      [
        'link_optional' => [
          'name' => 'link_optional',
          'type' => 'link',
        ],
        'link_required' => [
          'name' => 'link_required',
          'type' => 'link',
        ],
        'link_bare' => [
          'name' => 'link_bare',
          'type' => 'link',
        ],
      ],
      [
        'link_optional' => [
          'label' => 'Optional title link',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkType::LINK_GENERIC,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
          'title' => DRUPAL_OPTIONAL,
          'enabled_attributes' => $enabled_attributes,
          'widget_default_open' => LinkType::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
        ],
        'link_required' => [
          'label' => 'Required title link',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkType::LINK_GENERIC,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
          'title' => DRUPAL_REQUIRED,
          'enabled_attributes' => $enabled_attributes,
          'widget_default_open' => LinkType::WIDGET_OPEN_EXPANDED,
        ],
        'link_bare' => [
          'label' => 'Bare link',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkType::LINK_GENERIC,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
          'title' => DRUPAL_DISABLED,
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

    $widget_defaults = [
      'type' => 'link_default',
    ];

    $this->setFormDisplay('field_test', [
      'link_optional' => [
        'weight' => 0,
        'label' => 'Optional title link',
        'placeholder_url' => 'https://example.com/optional',
        'placeholder_title' => 'Optional link text',
        'maxlength' => 50,
        'maxlength_js' => FALSE,
      ] + $widget_defaults,
      'link_required' => [
        'weight' => 1,
        'label' => 'Required title link',
        'placeholder_url' => 'https://example.com/required',
        'placeholder_title' => 'Required link text',
        'maxlength' => 100,
        'maxlength_js' => FALSE,
      ] + $widget_defaults,
      'link_bare' => [
        'weight' => 2,
        'label' => 'Bare link',
        'placeholder_url' => 'https://example.com/bare',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
      ] + $widget_defaults,
    ]);
  }

  /**
   * Tests the placeholder and maxlength widget settings via form display.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[link_optional]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals(
      $base . '[placeholder_url]',
      'https://example.com/optional'
    );
    $assert->fieldValueEquals(
      $base . '[placeholder_title]',
      'Optional link text'
    );
    $assert->fieldValueEquals($base . '[maxlength]', '50');

    $this->submitForm([
      $base . '[placeholder_url]' => 'https://updated.example.com',
      $base . '[placeholder_title]' => 'Updated link text',
    ], 'field_test_plugin_settings_update');

    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][link_optional][uri]"]',
      'placeholder',
      'https://updated.example.com'
    );
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][link_optional][title]"]',
      'placeholder',
      'Updated link text'
    );
  }

  /**
   * Tests the maxlength_js setting, which requires the maxlength module.
   */
  public function testMaxlengthJsSetting(): void {
    $assert = $this->assertSession();
    $optional_path = self::FIELD_PATH . '[link_optional][maxlength_js]';
    $bare_path = self::FIELD_PATH . '[link_bare][maxlength_js]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    // Visible when title is enabled and the maxlength module is present.
    $assert->fieldExists($optional_path);
    // Not accessible when title is disabled, regardless of the module.
    $assert->fieldNotExists($bare_path);

    $this->submitForm([
      $optional_path => TRUE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->elementAttributeContains(
      'css',
      'input[name="field_test[0][link_optional][title]"]',
      'data-maxlength',
      '50'
    );
  }

  /**
   * Tests that the uri element label depends on the fieldset/container path.
   *
   * When title is disabled and no attributes are enabled, the widget wraps
   * in a plain container and the uri element takes on the subfield's own
   * label. Otherwise it wraps in a fieldset and the uri element is always
   * labeled 'URL'.
   */
  public function testUriLabelFollowsWrapperType(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementTextContains(
      'css',
      'label[for="edit-field-test-0-link-optional-uri"]',
      'URL'
    );
    $assert->elementTextContains(
      'css',
      'label[for="edit-field-test-0-link-bare-uri"]',
      'Bare link'
    );
  }

  /**
   * Tests title = disabled: no title field, uri alone is sufficient.
   */
  public function testTitleDisabled(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldNotExists('field_test[0][link_bare][title]');

    $this->submitForm([
      'title[0][value]' => 'Bare link node',
      'field_test[0][link_bare][uri]' => 'https://www.example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Bare link node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_test')->link_bare
    );
    $this->assertEmpty($node->get('field_test')->link_bare__title);
  }

  /**
   * Tests title = optional: uri alone is valid, but title alone is not.
   */
  public function testTitleOptional(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // A uri without a title is valid when title is optional.
    $this->submitForm([
      'title[0][value]' => 'Optional uri only node',
      'field_test[0][link_optional][uri]' => 'https://www.example.com',
    ], 'Save');
    $assert->pageTextContains('Optional uri only node');

    // A title without a uri is rejected regardless of title being optional.
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Optional title only node',
      'field_test[0][link_optional][title]' => 'Orphaned title',
    ], 'Save');
    $assert->pageTextContains(
      'The URL field is required when the Link text field is specified.'
    );
    $assert->pageTextNotContains('Optional title only node has been created');

    // Both together round-trip correctly.
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Optional both node',
      'field_test[0][link_optional][uri]' => 'https://www.example.com',
      'field_test[0][link_optional][title]' => 'Example site',
    ], 'Save');

    $node = $this->loadNodeByTitle('Optional both node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_test')->link_optional
    );
    $this->assertEquals(
      'Example site',
      $node->get('field_test')->link_optional__title
    );
  }

  /**
   * Tests title = required: both fields must be filled together.
   */
  public function testTitleRequired(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // A uri without a title is rejected when title is required.
    $this->submitForm([
      'title[0][value]' => 'Required uri only node',
      'field_test[0][link_required][uri]' => 'https://www.example.com',
    ], 'Save');
    $assert->pageTextContains(
      'Link text field is required if there is URL input.'
    );
    $assert->pageTextNotContains('Required uri only node has been created');

    // A title without a uri is also rejected.
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required title only node',
      'field_test[0][link_required][title]' => 'Orphaned title',
    ], 'Save');
    $assert->pageTextContains(
      'The URL field is required when the Link text field is specified.'
    );
    $assert->pageTextNotContains('Required title only node has been created');

    // Both together round-trip correctly.
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required both node',
      'field_test[0][link_required][uri]' => 'https://www.example.com',
      'field_test[0][link_required][title]' => 'Example site',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required both node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_test')->link_required
    );
    $this->assertEquals(
      'Example site',
      $node->get('field_test')->link_required__title
    );
  }

  /**
   * Tests that link attributes (target, rel, class) save and reload.
   *
   * The target is a select (per custom_field.custom_field_link_attributes.yml),
   * rel and class are plain text inputs.
   */
  public function testAttributesRoundTrip(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $target_selector = 'select[name="field_test[0][link_optional]'
      . '[options][attributes][target]"]';
    $assert->elementExists('css', $target_selector);

    $class_field = 'field_test[0][link_optional][options][attributes][class]';
    $this->submitForm([
      'title[0][value]' => 'Attributes node',
      'field_test[0][link_optional][uri]' => 'https://www.example.com',
      'field_test[0][link_optional][title]' => 'Example site',
      'field_test[0][link_optional][options][attributes][target]' => '_blank',
      'field_test[0][link_optional][options][attributes][rel]' => 'nofollow',
      $class_field => 'btn btn-primary',
    ], 'Save');

    $node = $this->loadNodeByTitle('Attributes node');
    $options = $node->get('field_test')->link_optional__options;

    $this->assertEquals('_blank', $options['attributes']['target']);
    $this->assertEquals('nofollow', $options['attributes']['rel']);
    $this->assertEquals(
      ['btn', 'btn-primary'],
      $options['attributes']['class']
    );

    // The class array should redisplay as a space-joined string.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][link_optional][options][attributes][class]',
      'btn btn-primary'
    );
  }

  /**
   * Tests that an empty submission stores NULL for uri, title and options.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty link node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty link node');
    foreach (['link_optional', 'link_required', 'link_bare'] as $subfield) {
      $value = $node->get('field_test')->{$subfield} ?? NULL;
      $this->assertTrue(
        $value === NULL || $value === '',
        sprintf('%s should be empty.', $subfield)
      );
    }
  }

  /**
   * Tests that the Attributes details element open state follows settings.
   *
   * The link_required (WIDGET_OPEN_EXPANDED) should always render open.
   * link_optional (default 'expandIfValuesSet') should render collapsed
   * with no existing values, and open once it has saved attribute
   * values.
   */
  public function testAttributesDefaultOpenBehavior(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementExists(
      'css',
      'details[open][data-drupal-selector*="link-required"]'
    );
    $assert->elementNotExists(
      'css',
      'details[open][data-drupal-selector*="link-optional"]'
    );

    $this->submitForm([
      'title[0][value]' => 'Open state node',
      'field_test[0][link_optional][uri]' => 'https://www.example.com',
      'field_test[0][link_optional][options][attributes][rel]' => 'nofollow',
    ], 'Save');

    $node = $this->loadNodeByTitle('Open state node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->elementExists(
      'css',
      'details[open][data-drupal-selector*="link-optional"]'
    );
  }

  /**
   * Sanity check that LinkType's declared constraints are actually applied.
   *
   * LinkType redeclares the same four constraints as UriType rather than
   * inheriting them, so this confirms they're wired correctly here too.
   */
  public function testExternalProtocolConstraintApplies(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Rejected protocol link node',
      'field_test[0][link_bare][uri]' => 'javascript:alert(1)',
    ], 'Save');

    $assert->pageTextContains("The path 'javascript:alert(1)' is invalid.");
    $assert->pageTextNotContains(
      'Rejected protocol link node has been created'
    );
  }

  /**
   * Tests that a required link uri is enforced.
   *
   * Distinct from testTitleRequired(): that covers the title subfield's
   * own DRUPAL_REQUIRED setting, this covers the overall link's
   * 'required' field setting forcing the uri itself to be non-empty.
   * Scoped to its own field rather than setUp()'s shared field_test, so
   * the other tests aren't forced to also submit a value for it.
   */
  public function testRequiredLinkUriValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_link_required',
      [
        'link_uri_required' => [
          'name' => 'link_uri_required',
          'type' => 'link',
        ],
      ],
      [
        'link_uri_required' => [
          'label' => 'Link uri required',
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

    $this->setFormDisplay('field_link_required', [
      'link_uri_required' => [
        'type' => 'link_default',
        'weight' => 0,
        'label' => 'Link uri required',
        'placeholder_url' => '',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists(
      'field_link_required[0][link_uri_required][uri]'
    );
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required link uri node',
    ], 'Save');
    $assert->pageTextNotContains('Required link uri node has been created');

    $uri_field = 'field_link_required[0][link_uri_required][uri]';
    $this->submitForm([
      $uri_field => 'https://www.example.com',
    ], 'Save');

    $node = $this->loadNodeByTitle('Required link uri node');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_link_required')->link_uri_required
    );
  }

  /**
   * Tests that disabled attributes are not rendered on link_bare.
   */
  public function testDisabledAttributesAbsent(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldNotExists(
      'field_test[0][link_bare][options][attributes][target]'
    );
    $assert->fieldNotExists(
      'field_test[0][link_bare][options][attributes][rel]'
    );
    $assert->fieldNotExists(
      'field_test[0][link_bare][options][attributes][class]'
    );
    // No Attributes details when nothing is enabled.
    $assert->elementNotExists(
      'css',
      'details[data-drupal-selector*="link-bare"]'
    );
  }

  /**
   * Tests that an internal path persists on a generic (both) link field.
   */
  public function testInternalPathPersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Internal path link node',
      'field_test[0][link_bare][uri]' => '/node/add',
    ], 'Save');

    $node = $this->loadNodeByTitle('Internal path link node');
    // Stored form may be internal:/node/add or the path as submitted.
    $uri = $node->get('field_test')->link_bare;
    $this->assertNotEmpty($uri);
    $this->assertStringContainsString('node/add', (string) $uri);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldExists('field_test[0][link_bare][uri]');
  }

  /**
   * Tests external-only link_type rejects an internal path.
   */
  public function testLinkTypeExternalOnlyRejectsInternal(): void {
    $assert = $this->assertSession();

    $this->createCustomField(
      'field_link_external',
      [
        'link_ext' => [
          'name' => 'link_ext',
          'type' => 'link',
        ],
      ],
      [
        'link_ext' => [
          'label' => 'External only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkType::LINK_EXTERNAL,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
          'title' => DRUPAL_DISABLED,
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

    $this->setFormDisplay('field_link_external', [
      'link_ext' => [
        'type' => 'link_default',
        'weight' => 0,
        'label' => 'External only',
        'placeholder_url' => '',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'External only rejects internal',
      'field_link_external[0][link_ext][uri]' => '/node/add',
    ], 'Save');

    $assert->pageTextContains("The path '/node/add' is invalid.");
    $assert->pageTextNotContains(
      'External only rejects internal has been created'
    );

    // External URL is accepted.
    $this->submitForm([
      'field_link_external[0][link_ext][uri]' => 'https://www.example.com',
    ], 'Save');
    $node = $this->loadNodeByTitle('External only rejects internal');
    $this->assertEquals(
      'https://www.example.com',
      $node->get('field_link_external')->link_ext
    );
  }

  /**
   * Tests internal-only link_type rejects an external URL.
   */
  public function testLinkTypeInternalOnlyRejectsExternal(): void {
    $assert = $this->assertSession();

    $this->createCustomField(
      'field_link_internal',
      [
        'link_int' => [
          'name' => 'link_int',
          'type' => 'link',
        ],
      ],
      [
        'link_int' => [
          'label' => 'Internal only',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkType::LINK_INTERNAL,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
          'title' => DRUPAL_DISABLED,
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

    $this->setFormDisplay('field_link_internal', [
      'link_int' => [
        'type' => 'link_default',
        'weight' => 0,
        'label' => 'Internal only',
        'placeholder_url' => '',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Internal only rejects external',
      'field_link_internal[0][link_int][uri]' => 'https://www.example.com',
    ], 'Save');

    $assert->pageTextContains("The path 'https://www.example.com' is invalid.");
    $assert->pageTextNotContains(
      'Internal only rejects external has been created'
    );

    // Internal path is accepted.
    $this->submitForm([
      'field_link_internal[0][link_int][uri]' => '/node/add',
    ], 'Save');
    $node = $this->loadNodeByTitle('Internal only rejects external');
    $uri = $node->get('field_link_internal')->link_int;
    $this->assertNotEmpty($uri);
    $this->assertStringContainsString('node/add', (string) $uri);
  }

  /**
   * Tests field_prefix_custom renders on internal-only links.
   *
   * Field_prefix is only applied when external links are not allowed
   * (UrlWidgetBase).
   */
  public function testFieldPrefixCustomRenders(): void {
    $assert = $this->assertSession();

    $this->createCustomField(
      'field_link_prefix',
      [
        'link_prefix' => [
          'name' => 'link_prefix',
          'type' => 'link',
        ],
      ],
      [
        'link_prefix' => [
          'label' => 'Prefixed internal',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkType::LINK_INTERNAL,
          'field_prefix' => 'custom',
          'field_prefix_custom' => 'https://intranet.example/',
          'title' => DRUPAL_DISABLED,
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

    $this->setFormDisplay('field_link_prefix', [
      'link_prefix' => [
        'type' => 'link_default',
        'weight' => 0,
        'label' => 'Prefixed internal',
        'placeholder_url' => '',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    // Trailing slash is stripped by rtrim() in UrlWidgetBase.
    $assert->pageTextContains('https://intranet.example');
  }

}
