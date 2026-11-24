<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for Functional tests of custom field formatter plugins.
 *
 * Provides shared setup, display configuration helpers, and sample node
 * creation so individual formatter tests stay focused on formatter-specific
 * behavior.
 *
 * Subclasses should set $displayType (and optionally $viewDisplay,
 * $fieldName, $defaultWrappers) and call configureFormatter() from setUp()
 * when they need a non-default formatter configuration.
 */
abstract class FormatterTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_test',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * The view display ID used by tests.
   *
   * @var string
   */
  protected string $viewDisplay = 'node.custom_field_entity_test.default';

  /**
   * The formatter plugin ID under test.
   *
   * Subclasses must set this to the plugin id of the formatter being tested
   * (e.g. 'custom_formatter', 'custom_inline', 'custom_table').
   *
   * @var string
   */
  protected string $displayType;

  /**
   * The field name under test.
   *
   * @var string
   */
  protected string $fieldName = 'field_test';

  /**
   * Default HTML wrapper settings applied to subfields when needed.
   *
   * Empty strings leave each wrapper unset. Subclasses may override with
   * concrete tags (e.g. 'h3', 'none') when a test requires specific markup.
   *
   * @var array<string, string>
   */
  protected array $defaultWrappers = [
    'field_wrapper_tag' => '',
    'field_wrapper_classes' => '',
    'field_tag' => '',
    'field_classes' => '',
    'label_tag' => '',
    'label_classes' => '',
  ];

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $field;

  /**
   * The custom fields on the test entity bundle.
   *
   * @var array|\Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected array $fields = [];

  /**
   * The field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An admin user with permission to administer node display.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The custom field type manager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer node display',
    ]);
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->customFieldTypeManager = $this->container->get('plugin.manager.custom_field_type');
    $this->dateFormatter = $this->container->get('date.formatter');
    $this->fields = $this->entityFieldManager->getFieldDefinitions('node', 'custom_field_entity_test');
    $this->field = $this->fields[$this->fieldName];
    $this->fieldStorage = FieldStorageConfig::loadByName('node', $this->fieldName);
  }

  /**
   * Returns the path to the manage display form for the test content type.
   *
   * @return string
   *   The manage display path.
   */
  protected function getManageDisplayPath(): string {
    return '/admin/structure/types/manage/custom_field_entity_test/display';
  }

  /**
   * Configures the view display component for the field under test.
   *
   * Loads the view display, sets the formatter type to $this->displayType,
   * merges any provided settings (and optional label), and saves.
   *
   * @param array $settings
   *   Formatter settings to apply. These are merged on top of any existing
   *   component settings (existing keys are overwritten by $settings).
   * @param string $label
   *   The field label display setting (above, inline, hidden, visually_hidden).
   *   Defaults to 'above'.
   * @param int $weight
   *   Optional weight for the component. Defaults to 1.
   */
  protected function configureFormatter(array $settings = [], string $label = 'above', int $weight = 1): void {
    $display = EntityViewDisplay::load($this->viewDisplay);
    $this->assertNotNull($display, sprintf('View display %s could not be loaded.', $this->viewDisplay));

    $component = $display->getComponent($this->fieldName) ?? [];
    $component['type'] = $this->displayType;
    $component['label'] = $label;
    $component['weight'] = $weight;
    $component['region'] = $component['region'] ?? 'content';
    // Top-level scalar settings (e.g. hide_header) must fully replace existing
    // values; array_merge keeps nested keys like 'fields' when not overridden.
    $component['settings'] = array_merge($component['settings'] ?? [], $settings);

    $display->setComponent($this->fieldName, $component)->save();
    // Ensure subsequent loads (node view, manage display) see the new config.
    $this->entityTypeManager->getStorage('entity_view_display')->resetCache([
      $this->viewDisplay,
    ]);
  }

  /**
   * Applies the default wrapper settings to every subfield in the component.
   *
   * Useful when a test needs consistent wrapper markup without hand-editing
   * each subfield entry.
   *
   * @param array<string, string>|null $wrappers
   *   Wrapper settings to apply. Defaults to $this->defaultWrappers.
   */
  protected function applyDefaultWrappers(?array $wrappers = NULL): void {
    $wrappers = $wrappers ?? $this->defaultWrappers;
    $display = EntityViewDisplay::load($this->viewDisplay);
    $this->assertNotNull($display);
    $component = $display->getComponent($this->fieldName);
    if (empty($component['settings']['fields']) || !is_array($component['settings']['fields'])) {
      return;
    }
    foreach (array_keys($component['settings']['fields']) as $field_name) {
      $component['settings']['fields'][$field_name]['wrappers'] = $wrappers;
    }
    $display->setComponent($this->fieldName, $component)->save();
  }

  /**
   * Creates a node of type custom_field_entity_test with sample field values.
   *
   * Provides a realistic baseline of scalar subfield values. Tests that need
   * files, images, entity references, or viewfields should pass those values
   * via $field_overrides (or build supporting entities first and include their
   * IDs).
   *
   * @param array $field_overrides
   *   Values merged into the field_test column data. Use this to add or
   *   override specific subfields (e.g. image fid, entity_reference target).
   * @param array $node_overrides
   *   Additional node properties (title, status, etc.) merged into the
   *   createNode() values.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node.
   */
  protected function createPopulatedNode(array $field_overrides = [], array $node_overrides = []): NodeInterface {
    $field_values = [
      'boolean' => TRUE,
      'string' => 'Test string',
      'string_long' => 'Test string long',
      'integer' => 42,
      'float' => 3.14,
      'decimal' => 42.42,
      'email' => 'test@example.com',
      'telephone' => '+1234567890',
      'uri' => 'http://www.example.com',
      'color' => 'FF0000',
      'datetime' => '2020-01-01T01:30:00',
      'daterange' => '2020-01-01T01:45:00',
      'daterange__end' => '2021-01-01T02:40:00',
      'map_string' => [
        'Value 1',
        'Value 2',
        'Value 3',
      ],
      'map' => [
        [
          'key' => 'key1',
          'value' => 'Value 1',
        ],
        [
          'key' => 'key2',
          'value' => 'Value 2',
        ],
        [
          'key' => 'key3',
          'value' => 'Value 3',
        ],
      ],
      'link' => 'http://www.example.com',
      'link__title' => 'Example link',
      'link__options' => [
        'attributes' => [
          'rel' => 'nofollow',
          'target' => '_blank',
          'class' => ['link-test'],
        ],
      ],
      'duration' => 604800,
    ];
    // Overrides must win over defaults (array union keeps left-hand keys).
    $field_values = array_merge($field_values, $field_overrides);

    $values = array_merge([
      'type' => 'custom_field_entity_test',
      'title' => 'Test Node',
      $this->fieldName => $field_values,
    ], $node_overrides);

    return $this->drupalCreateNode($values);
  }

  /**
   * Asserts that the formatter settings summary contains expected strings.
   *
   * Visits the manage display page and checks that each expected string
   * appears in the page text (typically inside the formatter summary).
   *
   * @param string[] $expected
   *   Strings that must appear on the manage display page.
   * @param string[] $unexpected
   *   Optional strings that must not appear.
   */
  protected function assertSettingsSummaryContains(array $expected, array $unexpected = []): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getManageDisplayPath());
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    foreach ($expected as $text) {
      $session->pageTextContains($text);
    }
    foreach ($unexpected as $text) {
      $session->pageTextNotContains($text);
    }
  }

  /**
   * Returns the current view display component for the field under test.
   *
   * @return array|null
   *   The component configuration, or NULL if not present.
   */
  protected function getFieldComponent(): ?array {
    $display = EntityViewDisplay::load($this->viewDisplay);
    return $display ? $display->getComponent($this->fieldName) : NULL;
  }

}
