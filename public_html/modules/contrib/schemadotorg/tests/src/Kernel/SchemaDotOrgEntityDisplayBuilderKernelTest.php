<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface;

/**
 * Tests the Schema.org entity display builder.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilder
 * @group schemadotorg
 */
class SchemaDotOrgEntityDisplayBuilderKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * The Schema.org entity display builder.
   */
  protected SchemaDotOrgEntityDisplayBuilderInterface $schemaEntityDisplayBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaEntityDisplayBuilder = $this->container->get('schemadotorg.entity_display_builder');
  }

  /**
   * Test Schema.org entity display builder.
   */
  public function testEntityDisplayBuilder(): void {
    $mapping = $this->createSchemaEntity('node', 'Thing');

    // Check getting default field weights.
    $default_field_weights = $this->schemaEntityDisplayBuilder->getDefaultFieldWeights();
    $this->assertEquals(2, $default_field_weights['name']);
    $this->assertEquals(3, $default_field_weights['title']);
    $this->assertEquals(5, $default_field_weights['alternateName']);
    $this->assertEquals(17, $default_field_weights['description']);

    // Check default field weights that exceed the 200 thresholds.
    $original_default_field_weights = $this->config('schemadotorg.settings')
      ->get('schema_properties.default_field_weights');
    $default_field_weights = [];
    for ($i = 0; $i < 250; $i++) {
      $default_field_weights[] = 'field_' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
    }
    $this->config('schemadotorg.settings')
      ->set('schema_properties.default_field_weights', $default_field_weights)
      ->save();
    $default_field_weights = $this->schemaEntityDisplayBuilder->getDefaultFieldWeights();
    $this->assertEquals(1, $default_field_weights['field_000']);
    $this->assertEquals(2, $default_field_weights['field_001']);
    $this->assertEquals(3, $default_field_weights['field_002']);
    $this->assertEquals(99, $default_field_weights['field_098']);
    $this->assertEquals(100, $default_field_weights['field_099']);
    $this->assertEquals(101, $default_field_weights['field_100']);
    $this->assertEquals(102, $default_field_weights['field_101']);
    $this->assertEquals(102, $default_field_weights['field_102']);
    $this->config('schemadotorg.settings')
      ->set('schema_properties.default_field_weights', $original_default_field_weights)
      ->save();

    // Check setting entity displays for a field.
    $this->schemaEntityDisplayBuilder->setFieldDisplays(
      [
        'entity_type' => 'node',
        'bundle' => 'thing',
        'field_name' => 'name',
        'schema_type' => 'Thing',
        'schema_property' => 'name',
      ],
      NULL,
      [],
      NULL,
      []
    );
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
    $entity_view_display = EntityViewDisplay::load('node.thing.default');
    $expected_value = [
      'settings' => [],
      'third_party_settings' => [],
      'weight' => 2,
      'region' => 'content',
    ];
    $this->assertEquals($expected_value, $entity_view_display->getComponent('name'));

    // Check settings the default component weights.
    $this->schemaEntityDisplayBuilder->setComponentWeights($mapping);
    \Drupal::entityTypeManager()->getStorage('entity_form_display')->resetCache();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
    $entity_form_display = EntityFormDisplay::load('node.thing.default');
    $components = $entity_form_display->getComponents();
    $this->assertEquals(200, $components['uid']['weight']);
    $this->assertEquals(210, $components['promote']['weight']);

    // Get the promote component
    // Check updating the default component weights for an entity display.
    $entity_form_display->setComponent('promote',
      ['weight' => -100] + $entity_form_display->getComponent('promote')
    );
    $this->schemaEntityDisplayBuilder->updateDisplayComponentWeights($entity_form_display);
    $component = $entity_form_display->getComponent('promote');
    $this->assertNotEquals(-100, $component['weight']);
    $this->assertEquals(210, $component['weight']);

    // Display updating default component weights.
    $mapping_type = $this->loadMappingType('node');
    $mapping_type->set('default_component_weights_update', FALSE);
    $mapping_type->save();

    // Check NOT updating the default component weights for an entity display.
    $entity_form_display->setComponent('promote',
      ['weight' => -100] + $entity_form_display->getComponent('promote')
    );
    $this->schemaEntityDisplayBuilder->updateDisplayComponentWeights($entity_form_display);
    $component = $entity_form_display->getComponent('promote');
    $this->assertEquals(-100, $component['weight']);
    $this->assertNotEquals(210, $component['weight']);

    // Check getting display form modes for a specific entity type.
    $this->assertEquals(['default' => 'default'], $this->schemaEntityDisplayBuilder->getFormModes('node', 'page'));

    // Check getting display view modes for a specific entity type.
    $this->assertEquals(['default' => 'default'], $this->schemaEntityDisplayBuilder->getViewModes('node', 'page'));

    // Hide the end date for the teaser display.
    $this->config('schemadotorg.settings')
      ->set('schema_properties.disable_entity_display', ['node--view--endDate'])
      ->save();
    // Create the event content type.
    $this->createSchemaEntity('node', 'Event');

    // Check Schema.org types default view display properties for Event.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = EntityFormDisplay::load('node.event.default');
    $expected_components = [
      'body',
      'created',
      'promote',
      'schema_duration',
      'schema_end_date',
      'schema_start_date',
      'status',
      'sticky',
      'title',
      'uid',
      'langcode',
      'revision_log',
    ];
    $this->assertEquals($expected_components, array_keys($entity_form_display->getComponents()));

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
    $entity_view_display = EntityViewDisplay::load('node.event.teaser');
    $expected_components = [
      'body',
      'links',
      'schema_start_date',
      'uid',
      'title',
      'created',
    ];
    $this->assertEquals($expected_components, array_keys($entity_view_display->getComponents()));
  }

}
