<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Tests the Schema.org mapping storage.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgMappingStorage
 * @group schemadotorg
 */
class SchemaDotOrgMappingStorageKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create page.
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    // Create Thing and Image node with mappings.
    NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ])->save();
    NodeType::create([
      'type' => 'image_object',
      'name' => 'ImageObject',
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'schema_properties' => [
        'title' => 'name',
        'image' => 'image',
      ],
      'additional_mappings' => [
        'WebPage' => [
          'schema_type' => 'WebPage',
          'schema_properties' => [
            'title' => 'name',
            'schema_image' => 'primaryImageOfPage',
            'schema_related_link' => 'relatedLink',
            'schema_significant_link' => 'significantLink',
          ],
        ],
      ],
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'image_object',
      'schema_type' => 'ImageObject',
      'schema_properties' => [
        'title' => 'name',
      ],
    ])->save();
  }

  /**
   * Test Schema.org mapping storage.
   */
  public function testSchemaDotOrgMappingStorage(): void {
    $page_node = Node::create(['type' => 'page', 'title' => 'Page']);
    $page_node->save();

    $thing_node = Node::create(['type' => 'thing', 'title' => 'Thing']);
    $thing_node->save();

    $image_node = Node::create(['type' => 'image_object', 'title' => 'Image']);
    $image_node->save();

    // Check determining if an entity is mapped to a Schema.org type.
    $this->assertFalse($this->getMappingStorage()->isEntityMapped($page_node));
    $this->assertTrue($this->getMappingStorage()->isEntityMapped($thing_node));

    // Check determining if an entity type and bundle are mapped to Schema.org.
    $this->assertFalse($this->getMappingStorage()->isBundleMapped('node', 'page'));
    $this->assertTrue($this->getMappingStorage()->isBundleMapped('node', 'thing'));

    // Check determining if a mapping type definition is valid.
    $this->assertFalse($this->getMappingStorage()->isValidType('test'));
    $this->assertFalse($this->getMappingStorage()->isValidType('node:Test'));
    $this->assertFalse($this->getMappingStorage()->isValidType('test:Thing'));
    $this->assertTrue($this->getMappingStorage()->isValidType('node:Thing'));

    // Check getting the Schema.org type for an entity and bundle.
    $this->assertEquals('Thing', $this->getMappingStorage()->getSchemaType('node', 'thing'));

    // Check getting the Schema.org property name for an entity field mapping.
    $this->assertEquals('name', $this->getMappingStorage()->getSchemaPropertyName('node', 'thing', 'title'));
    $this->assertNull($this->getMappingStorage()->getSchemaPropertyName('node', 'thing', 'not_field'));
    $this->assertNull($this->getMappingStorage()->getSchemaPropertyName('node', 'not_thing', 'thing'));

    // Check getting a Schema.org property's range includes.
    $this->assertEquals(['Question' => 'Question'], $this->getMappingStorage()->getSchemaPropertyRangeIncludes('FAQPage', 'mainEntity'));

    // Check getting a Schema.org property's target bundles.
    $this->assertEquals(['image_object' => 'image_object'], $this->getMappingStorage()->getSchemaPropertyTargetBundles('node', 'Thing', 'image'));
    $this->assertEquals([], $this->getMappingStorage()->getSchemaPropertyTargetBundles('media', 'Thing', 'image'));

    $this->assertEquals(['image_object' => 'image_object'], $this->getMappingStorage()->getSchemaPropertyTargetBundles('node', 'Thing', 'image'));
    $this->assertEquals([], $this->getMappingStorage()->getSchemaPropertyTargetBundles('media', 'Thing', 'image'));

    // Check getting Schema.org range includes target bundles.
    $this->assertEquals([], $this->getMappingStorage()->getRangeIncludesTargetBundles('node', ['Thing' => 'Thing']));
    $this->assertEquals(['image_object' => 'image_object'], $this->getMappingStorage()->getRangeIncludesTargetBundles('node', ['MediaObject' => 'MediaObject']));
    $this->assertEquals(['image_object' => 'image_object'], $this->getMappingStorage()->getRangeIncludesTargetBundles('node', ['ImageObject' => 'ImageObject']));
    $this->assertEquals(['thing' => 'thing'], $this->getMappingStorage()->getRangeIncludesTargetBundles('node', ['WebPage' => 'WebPage'], ['ignore_thing' => FALSE]));
    $this->assertEquals([], $this->getMappingStorage()->getRangeIncludesTargetBundles('node', ['WebPage' => 'WebPage'], ['ignore_additional_mappings' => TRUE]));

    // Check parsing a type.
    $this->assertEquals(
      ['node', NULL, 'Thing'],
      $this->getMappingStorage()->parseType('node:Thing')
    );
    $this->assertEquals(
      ['node', 'custom_thing', 'Thing'],
      $this->getMappingStorage()->parseType('node:custom_thing:Thing')
    );

    // Check loading Schema.org by type.
    $this->assertEquals('node.thing', $this->getMappingStorage()->loadByType('node:Thing')->id());
    $this->assertEquals('node.thing', $this->getMappingStorage()->loadByType('node:thing:Thing')->id());

    // Check loading by target entity id and bundle.
    $this->assertEquals('node.thing', $this->getMappingStorage()->loadByBundle('node', 'thing')->id());
    $this->assertNull($this->getMappingStorage()->loadByBundle('node', 'not_thing'));

    // Check loading by target entity id and Schema.org type.
    $this->assertEquals('node.thing', $this->getMappingStorage()->loadBySchemaType('node', 'Thing')->id());
    $this->assertNull($this->getMappingStorage()->loadBySchemaType('node', 'NotThing'));

    // Check loading multiple with children by target entity id and Schema.org type.
    $expected_types = [
      'node.image_object',
      'node.thing',
    ];
    $actual_types = array_keys($this->getMappingStorage()->loadMultipleBySchemaType('node', 'Thing'));
    $this->assertEquals($expected_types, $actual_types);
    $expected_types = [
      'node.thing',
    ];
    $actual_types = array_keys($this->getMappingStorage()->loadMultipleBySchemaType('node', 'WebPage'));
    $this->assertEquals($expected_types, $actual_types);

    // Check loading by entity.
    $this->assertEquals('node.thing', $this->getMappingStorage()->loadByEntity($thing_node)->id());
    $this->assertNull($this->getMappingStorage()->loadByEntity($page_node));
  }

}
