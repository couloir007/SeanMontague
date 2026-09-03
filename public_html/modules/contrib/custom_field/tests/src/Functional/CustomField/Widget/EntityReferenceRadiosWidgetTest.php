<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the entity_reference_radios widget.
 *
 * Covers empty_option (default N/A), radio options from referenceable
 * entities, create/edit, empty, and required (no empty option when required).
 * Uses node/page as the target type.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class EntityReferenceRadiosWidgetTest extends CustomFieldFunctionalTestBase {

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
      'title' => 'Radio Target Alpha',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->targets['beta'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Radio Target Beta',
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
          'label' => 'Node radios',
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
        'type' => 'entity_reference_radios',
        'weight' => 0,
        'label' => 'Node radios',
        'empty_option' => 'N/A',
      ],
    ]);
  }

  /**
   * Tests empty_option widget setting (default N/A).
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[ref]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[empty_option]', 'N/A');

    $this->submitForm([
      $base . '[empty_option]' => 'None',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $assert->pageTextContains('None');
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][ref][target_id]"][value=""]'
    );
  }

  /**
   * Tests radio options include referenceable entities and empty option.
   */
  public function testOptionsRender(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][ref][target_id]"]'
    );
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][ref][target_id]"][value="' . $this->targets['alpha']->id() . '"]'
    );
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][ref][target_id]"][value="' . $this->targets['beta']->id() . '"]'
    );
    // Non-required: empty option present.
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_test[0][ref][target_id]"][value=""]'
    );
    $assert->pageTextContains('N/A');
    $assert->pageTextContains('Radio Target Alpha');
    $assert->pageTextContains('Radio Target Beta');
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
      'title[0][value]' => 'ER radios node',
      'field_test[0][ref][target_id]' => $alpha->id(),
    ], 'Save');

    $node = $this->loadNodeByTitle('ER radios node');
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
   * Tests empty / N/A selection stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty ER radios node',
      'field_test[0][ref][target_id]' => '',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty ER radios node');
    $value = $node->get('field_test')->ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '' || $value === 0);
  }

  /**
   * Tests required radios: no empty option, empty submit rejected.
   */
  public function testRequiredValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_err_required',
      [
        'ref_required' => [
          'name' => 'ref_required',
          'type' => 'entity_reference',
          'target_type' => 'node',
        ],
      ],
      [
        'ref_required' => [
          'label' => 'Required radios ref',
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

    $this->setFormDisplay('field_err_required', [
      'ref_required' => [
        'type' => 'entity_reference_radios',
        'weight' => 0,
        'label' => 'Required radios ref',
        'empty_option' => 'N/A',
      ],
    ]);

    $this->drupalGet('node/add/page');

    // Required radios should not prepend the empty option.
    $assert->elementNotExists(
      'css',
      'input[type="radio"][name="field_err_required[0][ref_required][target_id]"][value=""]'
    );
    $assert->elementExists(
      'css',
      'input[type="radio"][name="field_err_required[0][ref_required][target_id]"][value="' . $this->targets['alpha']->id() . '"]'
    );

    $this->submitForm([
      'title[0][value]' => 'Required ER radios node',
    ], 'Save');
    $assert->pageTextNotContains('Required ER radios node has been created');

    $this->submitForm([
      'field_err_required[0][ref_required][target_id]' => $this->targets['alpha']->id(),
    ], 'Save');

    $node = $this->loadNodeByTitle('Required ER radios node');
    $this->assertEquals(
      $this->targets['alpha']->id(),
      $node->get('field_err_required')->ref_required
    );
  }

}
