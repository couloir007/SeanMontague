<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_custom_field\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests Schema.org Role support for Custom Field on alumni property.
 *
 * @group schemadotorg
 */
class SchemaDotOrgCustomFieldRoleKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field',
    'schemadotorg_custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_custom_field']);
  }

  /**
   * Ensure Organization.alumni custom field Role injects target_id bundles.
   */
  public function testRoleTargetBundles(): void {
    $this->appendSchemaTypeDefaultProperties('Organization', 'alumni');
    $this->appendSchemaTypeDefaultProperties('Organization', 'member');

    // Create Person and Organization schema entities (bundles, fields, etc.).
    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');

    // Load the Organization's member field config.
    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = FieldConfig::load('node.organization.schema_member');
    $expected_settings = [
      'type' => 'entity_reference_autocomplete',
      'weight' => 0,
      'check_empty' => TRUE,
      'widget_settings' => [
        'label' => 'Member',
        'settings' => [
          'description' => '',
          'description_display' => 'after',
          'size' => 60,
          'placeholder' => '',
          'required' => FALSE,
          'match_operator' => 'CONTAINS',
          'match_limit' => 10,
          'handler' => 'schemadotorg:node',
          'handler_settings' => [
            'target_type' => 'node',
            'schema_types' => ['Person'],
            'target_bundles' => ['person' => 'person'],
          ],
        ],
      ],
    ];
    $settings = $field_config->get('settings');
    $this->assertEquals($expected_settings, $settings['field_settings']['target_id']);

    // Load the Organization's alumni field config.
    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = FieldConfig::load('node.organization.schema_alumni');
    $settings = $field_config->get('settings');
    $this->assertArrayNotHasKey('target_id', $settings['field_settings']);
  }

}
