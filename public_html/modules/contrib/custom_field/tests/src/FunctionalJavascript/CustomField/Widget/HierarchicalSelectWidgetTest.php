<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\CustomField\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for hierarchical_select.
 *
 * Covers AJAX cascading selects: choosing a parent reveals children, selecting
 * a child stores the deepest tid, and force_deepest_level requires a leaf.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class HierarchicalSelectWidgetTest extends WebDriverTestBase {

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
    'taxonomy',
    'custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Root term with children.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $parentA;

  /**
   * Child of parent A.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $childA1;

  /**
   * Root term with no children.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $parentB;

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
      'access taxonomy overview',
    ]);
    $this->drupalLogin($admin);

    Vocabulary::create([
      'vid' => 'heir_test',
      'name' => 'Hierarchy Test',
    ])->save();

    $this->parentA = Term::create([
      'vid' => 'heir_test',
      'name' => 'Parent A',
    ]);
    $this->parentA->save();

    $this->childA1 = Term::create([
      'vid' => 'heir_test',
      'name' => 'Child A1',
      'parent' => $this->parentA->id(),
    ]);
    $this->childA1->save();

    $this->parentB = Term::create([
      'vid' => 'heir_test',
      'name' => 'Parent B Leaf',
    ]);
    $this->parentB->save();

    $this->createCustomField(
      'field_test',
      [
        'term' => [
          'name' => 'term',
          'type' => 'entity_reference',
          'target_type' => 'taxonomy_term',
        ],
      ],
      [
        'term' => [
          'label' => 'Hierarchy term',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'handler' => 'default:taxonomy_term',
          'handler_settings' => [
            'target_bundles' => ['heir_test' => 'heir_test'],
            'sort' => ['field' => 'name', 'direction' => 'asc'],
            'auto_create' => FALSE,
            'auto_create_bundle' => '',
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'term' => [
        'type' => 'hierarchical_select',
        'weight' => 0,
        'label' => 'Hierarchy term',
        'force_deepest_level' => FALSE,
        'level_labels' => TRUE,
      ],
    ]);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
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
   * Tests AJAX cascade: parent selection reveals child select and saves leaf.
   */
  public function testAjaxCascadeSelectAndSave(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS heir cascade node');

    $assert->fieldExists('field_test[0][term][levels][0]');
    $assert->fieldNotExists('field_test[0][term][levels][1]');

    // Selecting Parent A should AJAX-load the child level.
    $page->selectFieldOption(
      'field_test[0][term][levels][0]',
      (string) $this->parentA->id()
    );
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][term][levels][1]');

    $assert->optionExists(
      'field_test[0][term][levels][1]',
      (string) $this->childA1->id()
    );
    // Level label uses parent term name when level_labels is enabled.
    $assert->pageTextContains('Parent A');

    $page->selectFieldOption(
      'field_test[0][term][levels][1]',
      (string) $this->childA1->id()
    );

    $assert->assertWaitOnAjaxRequest();

    $page->pressButton('Save');
    $assert->waitForText('JS heir cascade node');

    $node = $this->loadNodeByTitle('JS heir cascade node');
    // massageFormValue uses the deepest selected level.
    $this->assertEquals(
      $this->childA1->id(),
      $node->get('field_test')->term
    );
  }

  /**
   * Tests selecting a parent without children stores that parent tid.
   */
  public function testAjaxSelectLeafRootStoresParent(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS heir leaf root node');

    $page->selectFieldOption(
      'field_test[0][term][levels][0]',
      (string) $this->parentB->id()
    );
    $assert->assertWaitOnAjaxRequest();

    // Parent B has no children — second level should not appear.
    $assert->fieldNotExists('field_test[0][term][levels][1]');

    $page->pressButton('Save');
    $assert->waitForText('JS heir leaf root node');

    $node = $this->loadNodeByTitle('JS heir leaf root node');
    $this->assertEquals(
      $this->parentB->id(),
      $node->get('field_test')->term
    );
  }

  /**
   * Tests force_deepest_level requires a child when one exists.
   */
  public function testForceDeepestLevelRequiresChild(): void {
    $assert = $this->assertSession();

    $this->setFormDisplay('field_test', [
      'term' => [
        'type' => 'hierarchical_select',
        'weight' => 0,
        'label' => 'Hierarchy term',
        'force_deepest_level' => TRUE,
        'level_labels' => TRUE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS force deepest incomplete');

    $page->selectFieldOption(
      'field_test[0][term][levels][0]',
      (string) $this->parentA->id()
    );
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][term][levels][1]');

    // Child level is marked required when force_deepest_level is TRUE.
    $assert->elementAttributeExists(
      'css',
      'select[name="field_test[0][term][levels][1]"]',
      'required'
    );

    // Submit without a child — node must not be created.
    $page->pressButton('Save');
    $assert->pageTextNotContains('JS force deepest incomplete has been created');

    // Fresh form: complete parent + child path and save.
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS force deepest complete');

    $page->selectFieldOption(
      'field_test[0][term][levels][0]',
      (string) $this->parentA->id()
    );
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForField('field_test[0][term][levels][1]');

    $page->selectFieldOption(
      'field_test[0][term][levels][1]',
      (string) $this->childA1->id()
    );

    $assert->assertWaitOnAjaxRequest();

    // Ensure the child selection is committed before submit.
    $assert->fieldValueEquals(
      'field_test[0][term][levels][1]',
      (string) $this->childA1->id()
    );

    $page->pressButton('Save');
    $assert->waitForText('has been created');

    $node = $this->loadNodeByTitle('JS force deepest complete');
    $this->assertEquals(
      $this->childA1->id(),
      $node->get('field_test')->term
    );
  }

}
