<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the entity_reference_select widget.
 *
 * Covers empty_option settings, select options from referenceable entities,
 * create/edit, empty and required. Uses node/page as the target type.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class EntityReferenceSelectWidgetTest extends CustomFieldFunctionalTestBase {

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

    $this->targets['alpha'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Select Target Alpha',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->targets['beta'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Select Target Beta',
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
          'label' => 'Node select',
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
        'type' => 'entity_reference_select',
        'weight' => 0,
        'label' => 'Node select',
        'empty_option' => '- Select -',
      ],
    ]);
  }

  /**
   * Tests empty_option widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[ref]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[empty_option]', '- Select -');

    $this->submitForm([
      $base . '[empty_option]' => '- Choose node -',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->pageTextContains('- Choose node -');
  }

  /**
   * Tests select options include referenceable entities.
   */
  public function testOptionsRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementExists(
      'css',
      'select[name="field_test[0][ref][target_id]"]'
    );
    $assert->optionExists(
      'field_test[0][ref][target_id]',
      (string) $this->targets['alpha']->id()
    );
    $assert->optionExists(
      'field_test[0][ref][target_id]',
      (string) $this->targets['beta']->id()
    );
    $assert->pageTextContains('Select Target Alpha');
    $assert->pageTextContains('Select Target Beta');
  }

  /**
   * Tests create and edit with a selected entity.
   */
  public function testCreateAndEditReference(): void {
    $assert = $this->assertSession();
    $alpha = $this->targets['alpha'];
    $beta = $this->targets['beta'];

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'ER select node',
      'field_test[0][ref][target_id]' => $alpha->id(),
    ], 'Save');

    $node = $this->loadNodeByTitle('ER select node');
    $this->assertEquals($alpha->id(), $node->get('field_test')->ref);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][ref][target_id]',
      (string) $alpha->id()
    );

    $this->submitForm([
      'field_test[0][ref][target_id]' => $beta->id(),
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals($beta->id(), $node->get('field_test')->ref);
  }

  /**
   * Tests empty selection stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty ER select node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty ER select node');
    $value = $node->get('field_test')->ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '' || $value === 0);
  }

  /**
   * Tests required select validation.
   */
  public function testRequiredValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_ers_required',
      [
        'ref_required' => [
          'name' => 'ref_required',
          'type' => 'entity_reference',
          'target_type' => 'node',
        ],
      ],
      [
        'ref_required' => [
          'label' => 'Required select ref',
          'check_empty' => FALSE,
          'required' => TRUE,
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
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_ers_required', [
      'ref_required' => [
        'type' => 'entity_reference_select',
        'weight' => 0,
        'label' => 'Required select ref',
        'empty_option' => '- Select -',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required ER select node',
    ], 'Save');
    $assert->pageTextNotContains('Required ER select node has been created');

    $this->submitForm([
      'field_ers_required[0][ref_required][target_id]' => $this->targets['alpha']->id(),
    ], 'Save');

    $node = $this->loadNodeByTitle('Required ER select node');
    $this->assertEquals(
      $this->targets['alpha']->id(),
      $node->get('field_ers_required')->ref_required
    );
  }

}
