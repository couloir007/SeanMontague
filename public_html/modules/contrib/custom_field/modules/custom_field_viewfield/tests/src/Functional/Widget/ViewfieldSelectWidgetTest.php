<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_viewfield\Functional\Widget;

use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the viewfield_select widget.
 *
 * Without JS, display_id is hidden until target_id is submitted and the form
 * rebuilds. Tests that need a display therefore submit twice: target_id first,
 * then display_id. AJAX display population is covered in FunctionalJavascript.
 *
 * @group custom_field
 * @group custom_field_viewfield
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_viewfield')]
#[RunTestsInSeparateProcesses]
class ViewfieldSelectWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'custom_field_viewfield',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // View with only page_1 allowed for this field.
    View::create([
      'id' => 'view_one',
      'label' => 'View One',
      'base_table' => 'node_field_data',
      'status' => TRUE,
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Master',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [],
        ],
        'page_1' => [
          'id' => 'page_1',
          'display_title' => 'Page One',
          'display_plugin' => 'page',
          'position' => 1,
          'display_options' => [
            'path' => 'view-one-page',
          ],
        ],
      ],
    ])->save();

    // View with both displays allowed.
    View::create([
      'id' => 'view_two',
      'label' => 'View Two',
      'base_table' => 'node_field_data',
      'status' => TRUE,
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Master',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [],
        ],
        'page_1' => [
          'id' => 'page_1',
          'display_title' => 'Page Two',
          'display_plugin' => 'page',
          'position' => 1,
          'display_options' => [
            'path' => 'view-two-page',
          ],
        ],
      ],
    ])->save();

    $this->createCustomField(
      'field_test',
      [
        'viewfield_test' => [
          'name' => 'viewfield_test',
          'type' => 'viewfield',
          'target_type' => 'view',
        ],
      ],
      [
        'viewfield_test' => [
          'label' => 'Viewfield test',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'force_default' => 0,
          'allowed_views' => [
            'view_one' => ['default' => 0, 'page_1' => 'page_1'],
            'view_two' => ['default' => 'default', 'page_1' => 'page_1'],
          ],
          'token_browser' => [
            'recursion_limit' => 3,
            'global_types' => FALSE,
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'viewfield_test' => [
        'type' => 'viewfield_select',
        'weight' => 0,
        'label' => 'Viewfield test',
        'empty_option' => '- None -',
      ],
    ]);
  }

  /**
   * Tests the empty_option widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[viewfield_test]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[empty_option]', '- None -');

    $this->submitForm([
      $base . '[empty_option]' => '- Choose a view -',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->optionExists(
      'field_test[0][viewfield_test][target_id]',
      '- Choose a view -'
    );
  }

  /**
   * Tests the View dropdown is limited to allowed_views.
   */
  public function testViewOptionsPopulatedFromAllowedViews(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->optionExists(
      'field_test[0][viewfield_test][target_id]',
      'View One'
    );
    $assert->optionExists(
      'field_test[0][viewfield_test][target_id]',
      'View Two'
    );
  }

  /**
   * Tests display options after target_id form rebuild (non-JS path).
   */
  public function testDisplayOptionsCascadeByAllowedViews(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // view_one: only page_1 is allowed.
    $this->submitForm([
      'title[0][value]' => 'View one node',
      'field_test[0][viewfield_test][target_id]' => 'view_one',
    ], 'Save');
    $assert->fieldExists('field_test[0][viewfield_test][display_id]');
    $assert->optionExists('field_test[0][viewfield_test][display_id]', 'page_1');
    $assert->optionNotExists('field_test[0][viewfield_test][display_id]', 'default');

    $this->submitForm([
      'field_test[0][viewfield_test][display_id]' => 'page_1',
    ], 'Save');

    $node = $this->loadNodeByTitle('View one node');
    $this->assertEquals('view_one', $node->get('field_test')->viewfield_test);
    $this->assertEquals(
      'page_1',
      $node->get('field_test')->viewfield_test__display
    );

    // view_two: both displays allowed.
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'View two node',
      'field_test[0][viewfield_test][target_id]' => 'view_two',
    ], 'Save');
    $assert->fieldExists('field_test[0][viewfield_test][display_id]');
    $assert->optionExists('field_test[0][viewfield_test][display_id]', 'default');
    $assert->optionExists('field_test[0][viewfield_test][display_id]', 'page_1');

    $this->submitForm([
      'field_test[0][viewfield_test][display_id]' => 'default',
    ], 'Save');

    $node = $this->loadNodeByTitle('View two node');
    $this->assertEquals('view_two', $node->get('field_test')->viewfield_test);
    $this->assertEquals(
      'default',
      $node->get('field_test')->viewfield_test__display
    );
  }

  /**
   * Tests arguments and items_to_display persist.
   */
  public function testArgumentsAndItemsToDisplay(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Arguments / items are always present (CSS-hidden via #states).
    $this->submitForm([
      'title[0][value]' => 'Advanced options node',
      'field_test[0][viewfield_test][target_id]' => 'view_two',
      'field_test[0][viewfield_test][view_options][arguments]' => '123/456',
      'field_test[0][viewfield_test][view_options][items_to_display]' => '5',
    ], 'Save');
    $assert->fieldExists('field_test[0][viewfield_test][display_id]');

    $this->submitForm([
      'field_test[0][viewfield_test][display_id]' => 'page_1',
    ], 'Save');

    $node = $this->loadNodeByTitle('Advanced options node');
    $this->assertEquals(
      '123/456',
      $node->get('field_test')->viewfield_test__arguments
    );
    $this->assertEquals(
      5,
      (int) $node->get('field_test')->viewfield_test__items
    );
  }

  /**
   * Tests edit form shows stored view, display, arguments, and items.
   */
  public function testEditPersistsValues(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Edit viewfield node',
      'field_test' => [
        'viewfield_test' => 'view_two',
        'viewfield_test__display' => 'page_1',
        'viewfield_test__arguments' => 'a/b',
        'viewfield_test__items' => 7,
      ],
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');

    $assert->fieldValueEquals(
      'field_test[0][viewfield_test][target_id]',
      'view_two'
    );
    $assert->fieldValueEquals(
      'field_test[0][viewfield_test][display_id]',
      'page_1'
    );
    $assert->fieldValueEquals(
      'field_test[0][viewfield_test][view_options][arguments]',
      'a/b'
    );
    $assert->fieldValueEquals(
      'field_test[0][viewfield_test][view_options][items_to_display]',
      '7'
    );
  }

  /**
   * Tests empty target_id stores NULL for the subfield.
   */
  public function testEmptyTargetIdStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty viewfield node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty viewfield node');
    $value = $node->get('field_test')->viewfield_test ?? NULL;
    $this->assertTrue($value === NULL || $value === '');

    $display = $node->get('field_test')->viewfield_test__display ?? NULL;
    $this->assertTrue($display === NULL || $display === '');
  }

  /**
   * Tests force_default hides the widget on the entity form.
   */
  public function testForceDefaultHidesWidget(): void {
    $assert = $this->assertSession();

    $field = $this->createCustomField(
      'field_viewfield_forced',
      [
        'viewfield_forced' => [
          'name' => 'viewfield_forced',
          'type' => 'viewfield',
          'target_type' => 'view',
        ],
      ],
      [
        'viewfield_forced' => [
          'label' => 'Viewfield forced',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'force_default' => 1,
          'allowed_views' => [
            'view_two' => ['default' => 'default', 'page_1' => 'page_1'],
          ],
          'token_browser' => [
            'recursion_limit' => 3,
            'global_types' => FALSE,
          ],
        ],
      ],
    );
    // Default value is required when force_default is enabled.
    $field->setDefaultValue([
      [
        'viewfield_forced' => 'view_two',
        'viewfield_forced__display' => 'default',
        'viewfield_forced__arguments' => NULL,
        'viewfield_forced__items' => NULL,
      ],
    ])->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_viewfield_forced', [
      'viewfield_forced' => [
        'type' => 'viewfield_select',
        'weight' => 0,
        'label' => 'Viewfield forced',
        'empty_option' => '- None -',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $assert->fieldNotExists(
      'field_viewfield_forced[0][viewfield_forced][target_id]'
    );
  }

  /**
   * Tests required target_id validation.
   */
  public function testTargetIdRequiredValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_viewfield_required',
      [
        'viewfield_required' => [
          'name' => 'viewfield_required',
          'type' => 'viewfield',
          'target_type' => 'view',
        ],
      ],
      [
        'viewfield_required' => [
          'label' => 'Viewfield required',
          'check_empty' => FALSE,
          'required' => TRUE,
          'description' => '',
          'force_default' => 0,
          'allowed_views' => [
            'view_one' => ['default' => 0, 'page_1' => 'page_1'],
          ],
          'token_browser' => [
            'recursion_limit' => 3,
            'global_types' => FALSE,
          ],
        ],
      ],
    );
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_viewfield_required', [
      'viewfield_required' => [
        'type' => 'viewfield_select',
        'weight' => 0,
        'label' => 'Viewfield required',
        'empty_option' => '- None -',
      ],
    ]);

    $this->drupalGet('node/add/page');

    $field = $assert->fieldExists(
      'field_viewfield_required[0][viewfield_required][target_id]'
    );
    $this->assertStringContainsString(
      'required',
      (string) $field->getAttribute('class')
    );

    $this->submitForm([
      'title[0][value]' => 'Required viewfield node',
    ], 'Save');

    $assert->pageTextNotContains(
      'Required viewfield node has been created'
    );
  }

}
