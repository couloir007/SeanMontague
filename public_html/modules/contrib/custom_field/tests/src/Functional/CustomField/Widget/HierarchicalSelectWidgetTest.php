<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the hierarchical_select widget.
 *
 * Covers widget settings, top-level options, selecting a root term (no AJAX),
 * empty/required, and edit path when a nested term is stored. Cascading
 * level AJAX is covered in FunctionalJavascript.
 *
 * Only applicable when the entity_reference target_type is taxonomy_term.
 *
 * Form keys: field_test[0][term][levels][{n}].
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class HierarchicalSelectWidgetTest extends CustomFieldFunctionalTestBase {

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
   * Vocabulary for hierarchical terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

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
   * Root term with no children (leaf).
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $parentB;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->vocabulary = Vocabulary::create([
      'vid' => 'heir_test',
      'name' => 'Hierarchy Test',
    ]);
    $this->vocabulary->save();

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
   * Tests force_deepest_level and level_labels settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[term]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    // Code default is TRUE for level_labels (docs currently say FALSE).
    $assert->fieldExists($base . '[force_deepest_level]');
    $assert->fieldExists($base . '[level_labels]');

    $this->submitForm([
      $base . '[force_deepest_level]' => TRUE,
      $base . '[level_labels]' => FALSE,
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    // Reload settings form and confirm persisted values.
    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');
    $assert->checkboxChecked($base . '[force_deepest_level]');
    $assert->checkboxNotChecked($base . '[level_labels]');
  }

  /**
   * Tests top-level select renders root terms.
   */
  public function testTopLevelOptionsRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementExists(
      'css',
      'select[name="field_test[0][term][levels][0]"]'
    );
    $assert->optionExists(
      'field_test[0][term][levels][0]',
      (string) $this->parentA->id()
    );
    $assert->optionExists(
      'field_test[0][term][levels][0]',
      (string) $this->parentB->id()
    );
    $assert->pageTextContains('Parent A');
    $assert->pageTextContains('Parent B Leaf');

    // Child is not a root option.
    $assert->optionNotExists(
      'field_test[0][term][levels][0]',
      (string) $this->childA1->id()
    );

    // Without selecting a parent, no second level is present.
    $assert->fieldNotExists('field_test[0][term][levels][1]');
  }

  /**
   * Tests selecting a leaf root term stores that tid.
   */
  public function testSelectRootLeafTerm(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Heir root leaf node',
      'field_test[0][term][levels][0]' => $this->parentB->id(),
    ], 'Save');

    $node = $this->loadNodeByTitle('Heir root leaf node');
    $this->assertEquals(
      $this->parentB->id(),
      $node->get('field_test')->term
    );
  }

  /**
   * Tests edit form shows full path for a nested stored term.
   */
  public function testEditNestedTermShowsLevels(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Heir nested node',
      'field_test' => [
        'term' => $this->childA1->id(),
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');

    // Path to root: Parent A → Child A1.
    $assert->fieldValueEquals(
      'field_test[0][term][levels][0]',
      (string) $this->parentA->id()
    );
    $assert->fieldValueEquals(
      'field_test[0][term][levels][1]',
      (string) $this->childA1->id()
    );
    // With level_labels TRUE, parent name is used as the child select title.
    $assert->pageTextContains('Parent A');
  }

  /**
   * Tests empty selection stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty heir node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty heir node');
    $value = $node->get('field_test')->term ?? NULL;
    $this->assertTrue($value === NULL || $value === '' || $value === 0);
  }

  /**
   * Tests required hierarchical field validation.
   */
  public function testRequiredValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_heir_required',
      [
        'term_req' => [
          'name' => 'term_req',
          'type' => 'entity_reference',
          'target_type' => 'taxonomy_term',
        ],
      ],
      [
        'term_req' => [
          'label' => 'Required hierarchy',
          'check_empty' => FALSE,
          'required' => TRUE,
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
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_heir_required', [
      'term_req' => [
        'type' => 'hierarchical_select',
        'weight' => 0,
        'label' => 'Required hierarchy',
        'force_deepest_level' => FALSE,
        'level_labels' => TRUE,
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required heir node',
    ], 'Save');
    $assert->pageTextNotContains('Required heir node has been created');

    $this->submitForm([
      'field_heir_required[0][term_req][levels][0]' => $this->parentB->id(),
    ], 'Save');

    $node = $this->loadNodeByTitle('Required heir node');
    $this->assertEquals(
      $this->parentB->id(),
      $node->get('field_heir_required')->term_req
    );
  }

}
