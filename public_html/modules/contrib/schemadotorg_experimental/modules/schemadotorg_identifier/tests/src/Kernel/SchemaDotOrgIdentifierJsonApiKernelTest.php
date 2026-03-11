<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_identifier\Kernel;

use Drupal\Tests\schemadotorg_jsonapi\Kernel\SchemaDotOrgJsonApiKernelTestBase;

/**
 * Tests the functionality of the Schema.org identifier JSON:API support.
 *
 * @covers schemadotorg_identifier_jsonapi_resource_config_presave()
 * @group schemadotorg
 */
class SchemaDotOrgIdentifierJsonApiKernelTest extends SchemaDotOrgJsonApiKernelTestBase {

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
   * Test Schema.org identifier JSON:API support.
   */
  public function testIdentifierJsonApi(): void {
    $this->createSchemaEntity('node', 'MedicalTrial');

    // Check that JSON:API resource was created for Thing.
    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource */
    $resource = $this->resourceStorage->load('node--medical_trial');
    $resource_fields = $resource->get('resourceFields');
    $expected_result = [
      'disabled' => FALSE,
      'fieldName' => 'schema_identifier_irb',
      'publicName' => 'irb_number',
      'enhancer' => ['id' => ''],
    ];
    $this->assertEquals($expected_result, $resource_fields['schema_identifier_irb']);
  }

}
