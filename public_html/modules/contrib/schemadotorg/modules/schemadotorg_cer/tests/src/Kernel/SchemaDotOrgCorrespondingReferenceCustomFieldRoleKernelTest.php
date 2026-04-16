<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_cer\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests Schema.org Corresponding Entity References and custom field role support.
 *
 * @group schemadotorg
 */
class SchemaDotOrgCorrespondingReferenceCustomFieldRoleKernelTest extends SchemaDotOrgEntityKernelTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the cer.module has fixed its schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cer',
    'schemadotorg_cer',
    'custom_field',
    'schemadotorg_custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(self::$modules);

    \Drupal::moduleHandler()->loadInclude('schemadotorg_cer', 'install');
    schemadotorg_cer_install(FALSE);
  }

  /**
   * Test custom field corresponding entity references.
   */
  public function testCustomField(): void {
    $this->appendSchemaTypeDefaultProperties('Organization', 'member');
    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');

    $field_config = FieldConfig::load('node.organization.schema_member');
    $this->assertEquals('custom', $field_config->getType());

    $person_node = Node::create([
      'type' => 'person',
      'title' => 'Person',
    ]);
    $person_node->save();

    $organization_node = Node::create([
      'type' => 'organization',
      'title' => 'Organization',
    ]);
    $organization_node->save();

    /* ********************************************************************** */

    $this->assertNull($person_node->schema_member_of->target_id);
    $this->assertNull($organization_node->schema_member->target_id);

    // Add the person to the organization member fields.
    $organization_node->schema_member->target_id = $person_node->id();
    // @phpstan-ignore-next-line
    $organization_node->schema_member->role_name = 'President';
    $organization_node->save();

    // Reload the updated corresponding person node.
    $person_node = Node::load($person_node->id());

    // Check that there is no corresponding entity reference.
    $this->assertEquals($organization_node->id(), $person_node->schema_member_of->target_id);
    $this->assertEquals($person_node->id(), $organization_node->schema_member->target_id);

    // Remove the page from person's member of field.
    $person_node->schema_member_of->setValue([]);
    $person_node->save();

    // Reload the updated corresponding page node.
    $organization_node = Node::load($organization_node->id());

    // Check that there are no entity reference btw the person and organization.
    $this->assertEmpty($person_node->schema_member_of->target_id);
    $this->assertEmpty($organization_node->schema_member->target_id);

  }

}
