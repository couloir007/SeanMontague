<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_field_parts\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg_jsonld\Kernel\SchemaDotOrgJsonLdKernelTestBase;

/**
 * Tests the functionality of the Schema.org field parts JSON-LD.
 *
 * @covers schemadotorg_field_parts_schemadotorg_jsonld_schema_type_entity_load()
 * @group schemadotorg
 */
class SchemaDotOrgFieldPartsJsonLdKernelTest extends SchemaDotOrgJsonLdKernelTestBase {

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
   * Test Schema.org field parts.
   */
  public function testFieldParts(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    $this->createSchemaEntity('node', 'WebContent');

    $node = Node::create([
      'type' => 'web_content',
      'title' => 'Something',
      'title_prefix' => '{Prefix}',
      'title_suffix' => '{Suffix}',
    ]);
    $node->save();

    /* ********************************************************************** */

    // Check adding prefix and suffix values to the Schema.property's value.
    $jsonld = $this->builder->buildEntity($node);
    $this->assertEquals('{Prefix}: Something - {Suffix}', $jsonld['name']);

    // Check that clearing the prefix delimiter remove the prefix value.
    $this->config('schemadotorg_field_parts.settings')
      ->set('prefix_delimiter', '')
      ->save();
    $jsonld = $this->builder->buildEntity($node);
    $this->assertEquals('Something - {Suffix}', $jsonld['name']);

    // Check that clearing the suffix delimiter remove the suffix value.
    $this->config('schemadotorg_field_parts.settings')
      ->set('suffix_delimiter', '')
      ->save();
    $jsonld = $this->builder->buildEntity($node);
    $this->assertEquals('Something', $jsonld['name']);

  }

}
