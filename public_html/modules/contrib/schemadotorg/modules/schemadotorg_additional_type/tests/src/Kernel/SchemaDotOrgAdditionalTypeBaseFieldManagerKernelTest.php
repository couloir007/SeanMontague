<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_additional_type\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\node\Entity\Node;
use Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManager;
use Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManagerInterface;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests Schema.org additional type base field manager.
 *
 * @group schemadotorg
 */
class SchemaDotOrgAdditionalTypeBaseFieldManagerKernelTest extends SchemaDotOrgEntityKernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_additional_type',
    'views',
  ];

  /**
   * The entity field manager.
   */
  protected EntityFieldManagerInterface $fieldManager;

  /**
   * The Schema.org additional type base field manager.
   */
  protected SchemaDotOrgAdditionalTypeBaseFieldManagerInterface $baseFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['schemadotorg_additional_type']);

    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');

    $this->fieldManager = $this->container->get('entity_field.manager');
    $this->baseFieldManager = $this->container->get('schemadotorg_additional_type.base_field_manager');
  }

  /**
   * Tests the additional type base field manager interface methods.
   */
  public function testBaseFieldManager(): void {
    // Create an event content type and an event block content type.
    $this->createSchemaEntity('node', 'Event');
    $this->createSchemaEntity('block_content', 'Event');

    // Get the entity type definitions.
    $node_entity_type = $this->entityTypeManager->getDefinition('node');
    $block_content_entity_type = $this->entityTypeManager->getDefinition('block_content');

    $event_node = Node::create([
      'type' => 'event',
      'title' => 'Music Event',
      'schema_event_type' => 'music_event',
    ]);
    $event_node->save();

    $event_block_content = BlockContent::create([
      'type' => 'event',
      'info' => 'Music Event Block',
      'schema_event_type' => 'music_event',
    ]);
    $event_block_content->save();

    // Check that the event node does not have base fields.
    $this->assertFalse($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertFalse($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));

    // Check that the event block content does not have base fields.
    $this->assertFalse($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertFalse($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));

    // Check that base field definitions for node and block content are empty.
    $this->assertEmpty($this->baseFieldManager->baseFieldInfo($node_entity_type));
    $this->assertEmpty($this->baseFieldManager->baseFieldInfo($block_content_entity_type));
    $this->config('schemadotorg_additional_type.settings')
      ->set('create_base_fields', ['node'])
      ->save();

    // Check that base field definitions for node returns both base fields.
    $node_base_fields = $this->baseFieldManager->baseFieldInfo($node_entity_type);
    $this->assertCount(2, $node_base_fields);
    $this->assertArrayHasKey(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE, $node_base_fields);
    $this->assertArrayHasKey(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE, $node_base_fields);
    $this->assertInstanceOf(BaseFieldDefinition::class, $node_base_fields[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE]);
    $this->assertInstanceOf(BaseFieldDefinition::class, $node_base_fields[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE]);

    // Check that base field definitions for block content remain empty.
    $this->assertEmpty($this->baseFieldManager->baseFieldInfo($block_content_entity_type));

    // Check that node base fields are created when node is enabled.
    $this->baseFieldManager->syncBaseFields([], ['node']);

    // Check that the event node with the additional type now has base fields.
    $event_node = $this->reloadEntity($event_node);
    $this->assertTrue($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertTrue($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));
    $this->assertEquals('event--music_event', $event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE)->value);
    $this->assertEquals('music_event', $event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE)->value);

    // Check that the event block content still does not have base fields.
    $event_block_content = $this->reloadEntity($event_block_content);
    $this->assertFalse($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertFalse($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));

    // Check that the other event node without the additional type now has base fields.
    $other_event_node = Node::create([
      'type' => 'event',
      'title' => 'Event Without Type',
    ]);
    $other_event_node->save();
    $this->assertEquals('event', $other_event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE)->value);
    $this->assertNull($other_event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE)->value);

    // Check that base field values as update on entity save.
    $other_event_node->get('schema_event_type')->value = 'dance_event';
    $other_event_node->save();
    $this->assertEquals('event--dance_event', $other_event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE)->value);
    $this->assertEquals('dance_event', $other_event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE)->value);

    $node_field_storage_definitions = $this->fieldManager->getBaseFieldDefinitions('node');

    // Check allowed values for 'schemadotorg_additional_type' base field.
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $additional_type_definition */
    $additional_type_definition = $node_field_storage_definitions[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE];
    $additional_type_allowed_values = SchemaDotOrgAdditionalTypeBaseFieldManager::allowedValuesCallback($additional_type_definition);
    $this->assertNotEmpty($additional_type_allowed_values);
    $this->assertArrayHasKey('music_event', $additional_type_allowed_values);
    $this->assertEquals('Music Event', $additional_type_allowed_values['music_event']);
    $this->assertArrayHasKey('dance_event', $additional_type_allowed_values);
    $this->assertEquals('Dance Event', $additional_type_allowed_values['dance_event']);

    // Check allowed values for 'schemadotorg_full_type' base field.
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $full_type_definition */
    $full_type_definition = $node_field_storage_definitions[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE];
    $full_type_allowed_values = SchemaDotOrgAdditionalTypeBaseFieldManager::allowedValuesCallback($full_type_definition);
    $this->assertNotEmpty($full_type_allowed_values);
    $this->assertArrayHasKey('event', $full_type_allowed_values);
    $this->assertArrayHasKey('event--music_event', $full_type_allowed_values);
    $this->assertEquals('Event', $full_type_allowed_values['event']);
    $this->assertEquals('Event: Music Event', $full_type_allowed_values['event--music_event']);

    $additional_type_filter_options = $this->baseFieldManager->getAllowedValues('node', SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE);
    $this->assertNotEmpty($additional_type_filter_options);
    $this->assertArrayHasKey('music_event', $additional_type_filter_options);
    $this->assertEquals('Music Event', $additional_type_filter_options['music_event']);
    $this->assertArrayHasKey('dance_event', $additional_type_filter_options);
    $this->assertEquals('Dance Event', $additional_type_filter_options['dance_event']);

    $full_type_filter_options = $this->baseFieldManager->getAllowedValues('node', SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE);
    $this->assertNotEmpty($full_type_filter_options);
    $this->assertArrayHasKey('event--music_event', $full_type_filter_options);
    $this->assertEquals('Event: Music Event', $full_type_filter_options['event--music_event']);

    // Check that the views table and base field information is altered for node.
    $views_data = $this->container->get('views.views_data')->get('node_field_data');
    $additional_type_filter = $views_data[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE]['filter'];
    $this->assertEquals('in_operator', $additional_type_filter['id']);
    $this->assertEquals('\Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManager::viewsFilterOptionsCallback', $additional_type_filter['options callback']);
    $this->assertEquals(
      [
        'node',
        SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE,
      ],
      $additional_type_filter['options arguments']
    );

    $full_type_filter = $views_data[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE]['filter'];
    $this->assertEquals('in_operator', $full_type_filter['id']);
    $this->assertEquals('\Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManager::viewsFilterOptionsCallback', $full_type_filter['options callback']);
    $this->assertEquals(
      [
        'node',
        SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE,
      ],
      $full_type_filter['options arguments']
    );

    // Check that block content base fields are created when block content is enabled.
    $this->config('schemadotorg_additional_type.settings')
      ->set('create_base_fields', ['node', 'block_content'])
      ->save();
    $this->baseFieldManager->syncBaseFields(['node'], ['node', 'block_content']);

    $block_content_base_fields = $this->baseFieldManager->baseFieldInfo($block_content_entity_type);
    $this->assertCount(2, $block_content_base_fields);
    $this->assertArrayHasKey(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE, $block_content_base_fields);
    $this->assertArrayHasKey(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE, $block_content_base_fields);

    $event_block_content = $this->reloadEntity($event_block_content);
    $this->assertTrue($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertTrue($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));
    $this->assertEquals('event--music_event', $event_block_content->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE)->value);
    $this->assertEquals('music_event', $event_block_content->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE)->value);

    // Check that the views table and base field information is altered for block content.
    $this->container->get('views.views_data')->clear();
    $block_content_views_data = $this->container->get('views.views_data')->get('block_content_field_data');
    $block_content_additional_type_filter = $block_content_views_data[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE]['filter'];
    $this->assertEquals('in_operator', $block_content_additional_type_filter['id']);
    $this->assertEquals('\Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManager::viewsFilterOptionsCallback', $block_content_additional_type_filter['options callback']);
    $this->assertEquals(
      [
        'block_content',
        SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE,
      ],
      $block_content_additional_type_filter['options arguments']
    );

    // Check that node base field definitions are deleted when node is disabled.
    $this->config('schemadotorg_additional_type.settings')
      ->set('create_base_fields', ['block_content'])
      ->save();
    $this->baseFieldManager->syncBaseFields(['block_content', 'node'], ['block_content']);
    $this->assertEmpty($this->baseFieldManager->baseFieldInfo($node_entity_type));
    $this->assertCount(2, $this->baseFieldManager->baseFieldInfo($block_content_entity_type));

    $event_node = $this->reloadEntity($event_node);
    $this->assertFalse($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertFalse($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));

    $event_block_content = $this->reloadEntity($event_block_content);
    $this->assertTrue($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertTrue($event_block_content->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));

    // Check that node base field definitions are created when node is re-enabled.
    $this->config('schemadotorg_additional_type.settings')
      ->set('create_base_fields', ['block_content', 'node'])
      ->save();
    $this->baseFieldManager->syncBaseFields(['block_content'], ['block_content', 'node']);

    $event_node = $this->reloadEntity($event_node);
    $this->assertTrue($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE));
    $this->assertTrue($event_node->hasField(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE));
    $this->assertEquals('event--music_event', $event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE)->value);
    $this->assertEquals('music_event', $event_node->get(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE)->value);
  }

  /**
   * {@inheritdoc}
   */
  protected function reloadEntity(EntityInterface $entity): ContentEntityInterface {
    $this->container->get('entity_type.manager')->clearCachedDefinitions();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::reloadEntity($entity);
    return $entity;
  }

}
