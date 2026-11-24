<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\CustomField\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for entity_reference_autocomplete.
 *
 * Complements the BrowserTestBase suite by exercising AJAX autocomplete
 * suggestions: CONTAINS matching, selecting a suggestion, and match_limit.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class EntityReferenceAutocompleteWidgetTest extends WebDriverTestBase {

  use CustomFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'node',
    'custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Referenceable page nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $targets = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $body = FieldConfig::loadByName('node', 'page', 'body');
    $body?->delete();

    $admin = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access content',
      'create page content',
      'edit any page content',
    ]);
    $this->drupalLogin($admin);

    $this->targets['alpha'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Target Alpha Unique',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->targets['beta'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Target Beta Unique',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->targets['gamma'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Target Gamma Unique',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->createCustomField(
      'field_test',
      [
        'ref' => [
          'name' => 'ref',
          'type' => 'entity_reference',
          'target_type' => 'node',
        ],
      ],
      [
        'ref' => [
          'label' => 'Node reference',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'handler' => 'default:node',
          'handler_settings' => [
            'target_bundles' => ['page' => 'page'],
            'sort' => ['field' => '_none', 'direction' => 'ASC'],
            'auto_create' => FALSE,
            'auto_create_bundle' => '',
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'ref' => [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'label' => 'Node reference',
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
        'size' => 60,
        'placeholder' => '',
      ],
    ]);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
   *
   * Mirrors CustomFieldFunctionalTestBase::setFormDisplay().
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $subfield_widgets
   *   Keyed by subfield name with widget settings.
   * @param string $widget_type
   *   Overall widget plugin id.
   * @param array $widget_settings
   *   Overall widget settings.
   * @param string $bundle
   *   Content type bundle.
   * @param string $mode
   *   Form mode.
   */
  protected function setFormDisplay(
    string $field_name,
    array $subfield_widgets,
    string $widget_type = 'custom_stacked',
    array $widget_settings = [],
    string $bundle = 'page',
    string $mode = 'default',
  ): void {
    $form_display = EntityFormDisplay::load("node.{$bundle}.{$mode}");
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ]);
    }

    $settings = $widget_settings + [
      'wrapper' => 'details',
      'open' => TRUE,
      'fields' => $subfield_widgets,
    ];

    $form_display->setComponent($field_name, [
      'type' => $widget_type,
      'weight' => 10,
      'region' => 'content',
      'settings' => $settings,
    ])->save();
  }

  /**
   * Tests autocomplete suggestions appear and selecting one fills the field.
   */
  public function testAutocompleteSuggestionsAndSelect(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS autocomplete node');

    $field_name = 'field_test[0][ref][target_id]';
    $assert->fieldExists($field_name);

    // Partial CONTAINS match should list Alpha.
    $page->fillField($field_name, 'Alpha Uni');
    $assert->waitOnAutocomplete();

    $assert->elementExists('css', 'ul.ui-autocomplete li');
    $assert->pageTextContains('Target Alpha Unique');

    // Select the first suggestion.
    $result = $page->find('css', 'ul.ui-autocomplete li:first-child a');
    $this->assertNotNull($result);
    $result->click();

    // Field should now contain Label (nid).
    $expected = 'Target Alpha Unique (' . $this->targets['alpha']->id() . ')';
    $assert->fieldValueEquals($field_name, $expected);

    $page->pressButton('Save');
    $assert->waitForText('JS autocomplete node');

    $node = $this->loadNodeByTitle('JS autocomplete node');
    $this->assertEquals(
      $this->targets['alpha']->id(),
      $node->get('field_test')->ref
    );
  }

  /**
   * Tests match_limit restricts the number of suggestions.
   */
  public function testMatchLimitRestrictsSuggestions(): void {
    $assert = $this->assertSession();

    // Tighten match_limit to 1.
    $this->setFormDisplay('field_test', [
      'ref' => [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'label' => 'Node reference',
        'match_operator' => 'CONTAINS',
        'match_limit' => 1,
        'size' => 60,
        'placeholder' => '',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    // "Target" matches Alpha, Beta, and Gamma — limit should show only 1.
    $page->fillField('field_test[0][ref][target_id]', 'Target');
    $assert->waitOnAutocomplete();

    $items = $page->findAll('css', 'ul.ui-autocomplete li');
    $this->assertCount(1, $items);
  }

}
