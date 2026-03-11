<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Functional;

use Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage;

/**
 * Tests the functionality of the Schema.org mapping type form.
 *
 * @covers \Drupal\schemadotorg\Form\SchemaDotOrgMappingTypeForm
 * @group schemadotorg
 */
class SchemaDotOrgMappingTypeFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'node', 'field_ui'];

  /**
   * The Schema.org mapping type storage.
   */
  protected SchemaDotOrgMappingTypeStorage $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set Schema.org mapping type storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');

  }

  /**
   * Test Schema.org mapping type form.
   */
  public function testSchemaDotOrgMappingTypeForm(): void {
    $assert = $this->assertSession();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);

    // Check that editing and re-saving the mapping type does not alter the
    // expected values.
    $mapping_type = $this->storage->load('node');
    $mapping_type_value = $mapping_type->toArray();
    $this->drupalGet('admin/config/schemadotorg/types/node');
    $this->submitForm([], 'Save');
    $assert->responseContains('Updated <em class="placeholder">Content</em> mapping type.');
    $this->storage->resetCache();
    $mapping_type = $this->storage->load('node');
    $this->assertEquals($mapping_type_value, $mapping_type->toArray());

    // Create a node:Thing Schema.org mapping.
    $this->createSchemaEntity('node', 'Thing');

    // Check deleting a Schema.org type that has mappings assigned to it.
    $this->drupalGet('admin/config/schemadotorg/types/node/delete');
    $assert->responseContains('The <em class="placeholder">Content</em> Schema.org mapping type is used by 1 Schema.org mapping on your site. You can not remove this Schema.org mapping type until you have removed all of the <em class="placeholder">Content</em> Schema.org mappings.');

    // Login as root.
    $this->drupalLogin($this->rootUser);

    // Check default component weights warning message.
    $this->drupalGet('admin/structure/types/manage/thing/form-display');
    $assert->responseContains('The following components have hard code weights: <em class="placeholder">Authored by; Authored on; Promoted to front page; Sticky at top of lists; Published</em>.');
    $assert->responseContains('<td>Published<div><small>(Weight: 220)</small></div></td>');

    $this->drupalGet('/admin/config/schemadotorg/types/node', ['query' => ['destination' => '/admin/structure/types/manage/thing/form-display']]);
    $this->submitForm(['default_component_weights_update' => FALSE], 'Save');
    $assert->responseNotContains('The following components have hard code weights: <em class="placeholder">Authored by; Authored on; Promoted to front page; Sticky at top of lists; Published</em>.');
    $assert->responseNotContains('<td>Published<div><small>(Weight: 220)</small></div></td>');
  }

}
