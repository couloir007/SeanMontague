<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_identifier\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg_jsonld\Kernel\SchemaDotOrgJsonLdKernelTestBase;

/**
 * Tests the functionality of the Schema.org identifier JSON-LD.
 *
 * @covers schemadotorg_identifier_schemadotorg_jsonld_schema_type_entity_load()
 * @group schemadotorg
 */
class SchemaDotOrgIdentifierJsonLdKernelTest extends SchemaDotOrgJsonLdKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_identifier',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_identifier']);
  }

  /**
   * Test Schema.org identifier.
   */
  public function testIdentifier(): void {
    // Add Drupal's UUID as an identifier.
    $this->config('schemadotorg_identifier.settings')
      ->set('field_definitions.uuid', [])
      ->set('schema_types.Thing', ['uuid'])
      ->save();

    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    $this->createSchemaEntity('node', 'MedicalTrial');

    $node = Node::create([
      'type' => 'medical_trial',
      'title' => 'Something',
      'schema_identifier_irb' => [
        'value' => '00000000',
      ],
    ]);
    $node->save();

    /* ********************************************************************** */

    // Check JSON-LD identifier property.
    $jsonld = $this->builder->buildEntity($node);
    $expected_identifier = [
      [
        '@type' => 'PropertyValue',
        'propertyID' => 'IRB number',
        'value' => '00000000',
      ],
      [
        '@type' => 'PropertyValue',
        'propertyID' => 'uuid',
        'value' => $node->uuid(),
      ],
    ];
    $this->assertEquals($expected_identifier, $jsonld['identifier']);
  }

}
