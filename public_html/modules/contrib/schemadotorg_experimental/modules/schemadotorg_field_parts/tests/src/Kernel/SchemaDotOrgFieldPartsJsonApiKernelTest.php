<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_identifier\Kernel;

use Drupal\Tests\schemadotorg_jsonapi\Kernel\SchemaDotOrgJsonApiKernelTestBase;

/**
 * Tests the functionality of the Schema.org field parts JSON:API support.
 *
 * @covers schemadotorg_field_parts_jsonapi_resource_config_presave()
 * @group schemadotorg
 */
class SchemaDotOrgFieldPartsJsonApiKernelTest extends SchemaDotOrgJsonApiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_field_parts',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_field_parts']);
  }

  /**
   * Test Schema.org field parts JSON:API support.
   */
  public function testFieldPartsJsonApi(): void {
    $this->createSchemaEntity('node', 'WebContent');

    // Check that JSON:API resource was created for WebContent with field parts.
    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource */
    $resource = $this->resourceStorage->load('node--web_content');
    $resource_fields = $resource->get('resourceFields');
    $expected_result = [
      'disabled' => FALSE,
      'fieldName' => 'title_prefix',
      'publicName' => 'title_prefix',
      'enhancer' => ['id' => ''],
    ];
    $this->assertEquals($expected_result, $resource_fields['title_prefix']);
    $expected_result = [
      'disabled' => FALSE,
      'fieldName' => 'title_suffix',
      'publicName' => 'title_suffix',
      'enhancer' => ['id' => ''],
    ];
    $this->assertEquals($expected_result, $resource_fields['title_suffix']);
  }

}
