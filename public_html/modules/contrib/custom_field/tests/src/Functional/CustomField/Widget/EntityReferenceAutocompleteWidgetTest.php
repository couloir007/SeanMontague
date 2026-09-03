<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the entity_reference_autocomplete widget.
 *
 * Covers widget settings (match_operator, match_limit, size, placeholder),
 * entity_autocomplete element rendering, create/edit with the "Label (id)"
 * value format, empty and required. Autocomplete suggestion AJAX is JS-only
 * and is not asserted here.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class EntityReferenceAutocompleteWidgetTest extends CustomFieldFunctionalTestBase {

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
      'title' => 'Target Alpha',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->targets['beta'] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Target Beta',
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
   * Formats an entity for entity_autocomplete submission.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The target node.
   *
   * @return string
   *   "Label (nid)" string expected by entity_autocomplete.
   */
  protected function autocompleteValue(NodeInterface $node): string {
    return $node->label() . ' (' . $node->id() . ')';
  }

  /**
   * Tests match_operator, match_limit, size, and placeholder settings.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[ref]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldValueEquals($base . '[match_operator]', 'CONTAINS');
    $assert->fieldValueEquals($base . '[match_limit]', '10');
    $assert->fieldValueEquals($base . '[size]', '60');
    $assert->fieldValueEquals($base . '[placeholder]', '');

    $this->submitForm([
      $base . '[match_operator]' => 'STARTS_WITH',
      $base . '[match_limit]' => 5,
      $base . '[size]' => 40,
      $base . '[placeholder]' => 'Search nodes',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');

    $this->drupalGet('node/add/page');
    $field = $assert->fieldExists('field_test[0][ref][target_id]');
    $this->assertEquals('40', $field->getAttribute('size'));
    $this->assertEquals('Search nodes', $field->getAttribute('placeholder'));
  }

  /**
   * Tests the autocomplete textfield renders.
   */
  public function testAutocompleteRenders(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldExists('field_test[0][ref][target_id]');
    $assert->elementExists(
      'css',
      'input[name="field_test[0][ref][target_id]"]'
    );
  }

  /**
   * Tests create and edit with a valid entity reference.
   */
  public function testCreateAndEditReference(): void {
    $assert = $this->assertSession();
    $alpha = $this->targets['alpha'];
    $beta = $this->targets['beta'];

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Autocomplete ref node',
      'field_test[0][ref][target_id]' => $this->autocompleteValue($alpha),
    ], 'Save');

    $node = $this->loadNodeByTitle('Autocomplete ref node');
    $this->assertEquals($alpha->id(), $node->get('field_test')->ref);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals(
      'field_test[0][ref][target_id]',
      $this->autocompleteValue($alpha)
    );

    $this->submitForm([
      'field_test[0][ref][target_id]' => $this->autocompleteValue($beta),
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals($beta->id(), $node->get('field_test')->ref);
  }

  /**
   * Tests empty value stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty autocomplete ref node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty autocomplete ref node');
    $value = $node->get('field_test')->ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '' || $value === 0);
  }

  /**
   * Tests required entity reference validation.
   */
  public function testRequiredValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_er_required',
      [
        'ref_required' => [
          'name' => 'ref_required',
          'type' => 'entity_reference',
          'target_type' => 'node',
        ],
      ],
      [
        'ref_required' => [
          'label' => 'Required reference',
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

    $this->setFormDisplay('field_er_required', [
      'ref_required' => [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'label' => 'Required reference',
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
        'size' => 60,
        'placeholder' => '',
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required autocomplete node',
    ], 'Save');
    $assert->pageTextNotContains('Required autocomplete node has been created');

    $this->submitForm([
      'field_er_required[0][ref_required][target_id]' => $this->autocompleteValue($this->targets['alpha']),
    ], 'Save');

    $node = $this->loadNodeByTitle('Required autocomplete node');
    $this->assertEquals(
      $this->targets['alpha']->id(),
      $node->get('field_er_required')->ref_required
    );
  }

}
