<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_additional_type\Kernel;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeFieldAccessHandlerInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;
use Drupal\Tests\schemadotorg_additional_type\Traits\SchemaDotOrgAdditionalTypeTestTrait;

/**
 * Tests Schema.org additional type field access handler.
 *
 * @group schemadotorg
 */
class SchemaDotOrgAdditionalTypeFieldAccessHandlerKernelTest extends SchemaDotOrgEntityKernelTestBase {
  use NodeCreationTrait;
  use SchemaDotOrgAdditionalTypeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_additional_type',
  ];

  /**
   * The Schema.org additional type field access handler.
   */
  protected SchemaDotOrgAdditionalTypeFieldAccessHandlerInterface $fieldAccess;

  /**
   * A thing node.
   */
  protected NodeInterface $node;

  /**
   * The Schema.org alternate name field definition.
   */
  protected FieldDefinitionInterface $fieldDefinition;

  /**
   * An authenticated account.
   */
  protected AccountInterface $account;

  /**
   * The admin user.
   */
  protected AccountInterface $admin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');

    $this->installConfig(['schemadotorg_additional_type']);

    $this->fieldAccess = $this->container->get('schemadotorg_additional_type.field_access_handler');

    $this->config('schemadotorg_additional_type.settings')
      ->set('default_types', ['Thing'])
      ->set('required_types', ['Thing'])
      ->set('default_allowed_values.Thing', [
        'person' => 'Person',
        'place' => 'Place',
        'organization' => 'Organization',
      ])
      ->save();

    $this->appendSchemaTypeDefaultProperties('Thing', ['alternateName', 'disambiguatingDescription']);
    $this->createSchemaEntity('node', 'Thing');

    $this->node = $this->createNode([
      'type' => 'thing',
      'schema_thing_type' => 'person',
      'title' => 'Thing',
      'schema_alternate_name' => 'Something',
    ]);

    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $this->entityTypeManager
      ->getStorage('field_config')
      ->load("node.thing.schema_alternate_name");
    $this->fieldDefinition = $field_config;

    $this->account = $this->createUser(['view own unpublished content']);

    $this->createAdminRole('admin');
    $this->admin = $this->createUser();
    $this->admin->addRole('admin')->save();
  }

  /**
   * Tests entity field access.
   */
  public function testEntityFieldAccess(): void {
    /* ********************************************************************** */
    // Additional types.
    /* ********************************************************************** */

    // Check additional type access with no conditions.
    $this->clearFieldAccessConditions();
    $this->node->set('schema_thing_type', 'person')->save();
    $this->assertFieldAccess('view');
    $this->assertFieldAccess('edit');
    $this->node->set('schema_thing_type', 'place')->save();
    $this->assertFieldAccess('view');
    $this->assertFieldAccess('edit');
    $this->node->set('schema_thing_type', 'organization')->save();
    $this->assertFieldAccess('view');
    $this->assertFieldAccess('edit');

    // Check additional type access to 'any' operation for 'person'.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('any', 'additional_types', 'person');
    $this->node->set('schema_thing_type', 'person')->save();
    $this->assertFieldAccess('view');
    $this->assertFieldAccess('edit');
    $this->node->set('schema_thing_type', 'place')->save();
    $this->assertNoFieldAccess('view');
    $this->assertNoFieldAccess('edit');
    $this->node->set('schema_thing_type', 'organization')->save();
    $this->assertNoFieldAccess('view');
    $this->assertNoFieldAccess('edit');

    // Check additional type access to 'view' operation for 'person'.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('view', 'additional_types', 'person');
    $this->node->set('schema_thing_type', 'person')->save();
    $this->assertFieldAccess('view');
    $this->assertFieldAccess('edit');
    $this->node->set('schema_thing_type', 'place')->save();
    $this->assertNoFieldAccess('view');
    $this->assertFieldAccess('edit');
    $this->node->set('schema_thing_type', 'organization')->save();
    $this->assertNoFieldAccess('view');
    $this->assertFieldAccess('edit');

    // Check additional type access to 'edit' operation for 'person'.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('edit', 'additional_types', 'person');
    $this->node->set('schema_thing_type', 'person')->save();
    $this->assertFieldAccess('view');
    $this->assertFieldAccess('edit');
    $this->node->set('schema_thing_type', 'place')->save();
    $this->assertFieldAccess('view');
    $this->assertNoFieldAccess('edit');
    $this->node->set('schema_thing_type', 'organization')->save();
    $this->assertFieldAccess('view');
    $this->assertNoFieldAccess('edit');

    /* ********************************************************************** */
    // Entity bundle.
    /* ********************************************************************** */

    // Check entity bundle.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('view', 'bundles', 'thing');
    $this->addFieldAccessCondition('edit', '!bundles', 'thing');
    $this->assertFieldAccess('view');
    $this->assertNoFieldAccess('edit');

    /* ********************************************************************** */
    // Entity access.
    /* ********************************************************************** */

    // Check entity access for checking field entity access conditions.
    $this->assertFalse($this->node->access('view', $this->account));
    $this->assertFalse($this->node->access('update', $this->account));
    $this->assertTrue($this->node->access('view', $this->admin));
    $this->assertTrue($this->node->access('update', $this->admin));

    // Check field entity access with no conditions.
    $this->clearFieldAccessConditions();
    $this->assertFieldAccess('view', $this->account);
    $this->assertFieldAccess('edit', $this->account);
    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

    // Check field entity access with 'update' access.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('any', 'entity_access', 'update');
    $this->assertNoFieldAccess('view', $this->account);
    $this->assertNoFieldAccess('edit', $this->account);
    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

    /* ********************************************************************** */
    // Permissions.
    /* ********************************************************************** */

    // Check permission access with 'view own unpublished content' permission.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('any', 'permissions', 'view own unpublished content');
    $this->assertFieldAccess('view', $this->account);
    $this->assertFieldAccess('edit', $this->account);

    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

    // Check permission access with 'administer nodes' permission.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('any', 'permissions', 'administer nodes');
    $this->assertNoFieldAccess('view', $this->account);
    $this->assertNoFieldAccess('edit', $this->account);

    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

    /* ********************************************************************** */
    // Roles.
    /* ********************************************************************** */

    // Check role access with 'authenticated' role.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('any', 'roles', 'authenticated');
    $this->assertFieldAccess('view', $this->account);
    $this->assertFieldAccess('edit', $this->account);

    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

    // Check role access with 'admin' role.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('any', 'roles', 'admin');
    $this->assertNoFieldAccess('view', $this->account);
    $this->assertNoFieldAccess('edit', $this->account);

    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

    /* ********************************************************************** */
    // Schema.org types.
    /* ********************************************************************** */

    // Check Schema.org types.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessCondition('view', 'schema_types', 'Thing');
    $this->addFieldAccessCondition('edit', '!schema_types', 'Thing');
    $this->assertFieldAccess('view');
    $this->assertNoFieldAccess('edit');

    /* ********************************************************************** */
    // Fall-through.
    /* ********************************************************************** */

    $this->clearFieldAccessConditions();
    $this->config('schemadotorg_additional_type.settings')
      ->set(
        "field_access.node--Thing.disambiguatingDescription",
        [],
      )
      ->save();
    $this->addFieldAccessCondition('view', 'schema_types', 'Thing');
    $this->addFieldAccessCondition('edit', '!schema_types', 'Thing');

    // Check that the  'disambiguatingDescription' has the same rules
    // as the 'alternateName' field.
    $this->assertFieldAccess('view');
    $this->assertNoFieldAccess('edit');
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $this->entityTypeManager
      ->getStorage('field_config')
      ->load("node.thing.schema_disambiguating_desc");
    $this->assertFieldAccess('view', NULL, $field_definition);
    $this->assertNoFieldAccess('edit', NULL, $field_definition);

    /* ********************************************************************** */
    // Operator.
    /* ********************************************************************** */

    // Check the 'and' operator.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessOperator('any', 'and');
    $this->addFieldAccessCondition('any', 'roles', 'authenticated');
    $this->addFieldAccessCondition('any', 'permissions', 'administer nodes');
    $this->assertNoFieldAccess('view', $this->account);
    $this->assertNoFieldAccess('edit', $this->account);

    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

    // Check the 'or' operator.
    $this->clearFieldAccessConditions();
    $this->addFieldAccessOperator('any', 'or');
    $this->addFieldAccessCondition('any', 'roles', 'authenticated');
    $this->addFieldAccessCondition('any', 'permissions', 'administer nodes');
    $this->assertFieldAccess('view', $this->account);
    $this->assertFieldAccess('edit', $this->account);

    $this->assertFieldAccess('view', $this->admin);
    $this->assertFieldAccess('edit', $this->admin);

  }

  /* ********************************************************************** */
  // Field access config helper methods.
  /* ********************************************************************** */

  /**
   * Clears all field access conditions.
   */
  protected function clearFieldAccessConditions(): void {
    $this->config('schemadotorg_additional_type.settings')
      ->set('field_access', [])
      ->save();
    $this->fieldAccess->resetCache();
  }

  /**
   * Adds a field access condition.
   *
   * @param string $operation
   *   The operation.
   * @param string $condition
   *   The condition.
   * @param array|string $values
   *   The values.
   */
  protected function addFieldAccessCondition(string $operation, string $condition, array|string $values): void {
    $this->config('schemadotorg_additional_type.settings')
      ->set(
        "field_access.node--Thing.alternateName.$operation.$condition",
        (array) $values,
      )
      ->save();
    $this->fieldAccess->resetCache();
  }

  /**
   * Adds a field access condition.
   *
   * @param string $operation
   *   The operation.
   * @param string $operator
   *   The operator.
   */
  protected function addFieldAccessOperator(string $operation, string $operator): void {
    $this->config('schemadotorg_additional_type.settings')
      ->set(
        "field_access.node--Thing.alternateName.$operation.operator",
        $operator,
      )
      ->save();
    $this->fieldAccess->resetCache();
  }

  /* ********************************************************************** */
  // Field access assertions.
  /* ********************************************************************** */

  /**
   * Asserts the field access.
   *
   * @param string $operation
   *   The operation to check (e.g., 'view', 'edit', 'delete').
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The user account for which to check access.
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   (optional) The field definition for which to check access.
   */
  protected function assertFieldAccess(string $operation, ?AccountInterface $account = NULL, ?FieldDefinitionInterface $field_definition = NULL): void {
    $is_forbidden = $this->fieldAccess->entityFieldAccess(
      $operation,
      $field_definition ?? $this->fieldDefinition,
      $account ?? $this->account,
      $this->node->get('schema_alternate_name'),
    )->isForbidden();

    $this->assertFalse($is_forbidden);
  }

  /**
   * Asserts the no field access.
   *
   * @param string $operation
   *   The operation to check (e.g., 'view', 'edit', 'delete').
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The user account for which to check access.
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   (optional) The field definition for which to check access.
   */
  protected function assertNoFieldAccess(string $operation, ?AccountInterface $account = NULL, ?FieldDefinitionInterface $field_definition = NULL): void {
    $is_forbidden = $this->fieldAccess->entityFieldAccess(
      $operation,
      $field_definition ?? $this->fieldDefinition,
      $account ?? $this->account,
      $this->node->get('schema_alternate_name'),
    )->isForbidden();

    $this->assertTrue($is_forbidden);
  }

}
