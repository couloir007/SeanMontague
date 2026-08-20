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
      'label' => 'Member',
      'check_empty' => TRUE,
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'handler' => 'default:node',
      'handler_settings' => [
        'target_type' => 'node',
        'schema_types' => [
          'Person',
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
