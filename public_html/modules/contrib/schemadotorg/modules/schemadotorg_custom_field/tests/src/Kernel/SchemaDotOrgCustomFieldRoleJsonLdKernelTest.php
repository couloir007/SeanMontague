<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_custom_field\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg_jsonld\Kernel\SchemaDotOrgJsonLdKernelTestBase;

/**
 * Tests the functionality of the Schema.org Custom Field Role JSON-LD.
 *
 * @covers \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldJsonLdManager
 * @group schemadotorg
 */
class SchemaDotOrgCustomFieldRoleJsonLdKernelTest extends SchemaDotOrgJsonLdKernelTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the custom field module has a schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field',
    'schemadotorg_custom_field',
    'schemadotorg_jsonld',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_custom_field']);
  }

  /**
   * Test Schema.org Custom Field Role JSON-LD manager.
   */
  public function testCustomFieldRole(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));
    $this->config('schemadotorg_custom_field.settings')
      ->set('default_schema_properties.member.schema_properties.roleName.allowed_values', [
        'president' => 'President',
        'vice_president' => 'Vice President',
      ])
      ->set('default_schema_properties.alumni.schema_properties.roleName.allowed_values', [
        'president' => 'President',
        'vice_president' => 'Vice President',
      ])
      ->save();

    $this->appendSchemaTypeDefaultProperties('Organization', 'member');
    $this->appendSchemaTypeDefaultProperties('Organization', 'alumni');

    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');

    $person = Node::create([
      'type' => 'person',
      'title' => 'John Doe',
      'schema_given_name' => [['value' => 'John']],
      'schema_family_name' => [['value' => 'Doe']],
    ]);
    $person->save();

    $organization = Node::create([
      'type' => 'organization',
      'title' => 'The Company',
      'schema_member' => [
        [
          'target_id' => $person->id(),
          'role_name' => 'president',
          'start_date' => '2026-01-03',
          'end_date' => '2025-12-08',
        ],
      ],
      'schema_alumni' => [
        [
          'role_name' => 'vice_president',
          'given_name' => 'Jane',
          'family_name' => 'Doe',
          'start_date' => '2026-01-03',
          'end_date' => '2025-12-08',
        ],
      ],
    ]);
    $organization->save();

    /* ********************************************************************** */

    $route_match = $this->manager->getEntityRouteMatch($organization);
    $jsonld = $this->builder->build($route_match);

    // Check that the member JSON-LD include the target entity's JSON-LD.
    $expected_member = [
      '@type' => 'Role',
      'member' => [
        '@type' => 'Person',
        '@url' => $person->toUrl('canonical', ['absolute' => TRUE])->toString(),
        'name' => 'John Doe',
        'givenName' => 'John',
        'familyName' => 'Doe',
      ],
      'roleName' => 'President',
      'startDate' => '2026-01-03',
      'endDate' => '2025-12-08',
    ];
    $this->assertEquals($expected_member, $jsonld['member'][0]);

    // Check that the alumni JSON-LD split role and type JSON-LD data.
    $expected_alumni = [
      '@type' => 'Role',
      'roleName' => 'Vice President',
      'startDate' => '2026-01-03',
      'endDate' => '2025-12-08',
      'alumni' => [
        '@type' => 'Person',
        'givenName' => 'Jane',
        'familyName' => 'Doe',
      ],
    ];
    $this->assertEquals($expected_alumni, $jsonld['alumni'][0]);
  }

}
