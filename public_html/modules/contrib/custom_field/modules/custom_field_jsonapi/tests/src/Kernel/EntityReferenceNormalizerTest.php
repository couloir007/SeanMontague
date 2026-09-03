<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_jsonapi\Kernel;

use Drupal\custom_field\Plugin\DataType\CustomFieldEntityReference;
use Drupal\node\Entity\Node;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the JSON:API entity reference custom field normalizer.
 *
 * @group custom_field
 *
 * @see \Drupal\custom_field_jsonapi\Normalizer\EntityReferenceNormalizer
 */
class EntityReferenceNormalizerTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field',
    'custom_field_jsonapi',
    'custom_field_test',
    'custom_field_viewfield',
    'field',
    'file',
    'image',
    'jsonapi',
    'node',
    'path',
    'path_alias',
    'serialization',
    'system',
    'user',
    'views',
  ];

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The node used as the reference target.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referencedNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('path_alias');
    $this->installConfig([
      'system',
      'custom_field_test',
      'node',
      'field',
      'user',
      'file',
      'image',
      'views',
    ]);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);

    $this->serializer = $this->container->get('serializer');

    // A referenceable target for the entity reference custom field subfield.
    $this->referencedNode = Node::create([
      'type' => 'article',
      'title' => 'Referenced article',
    ]);
    $this->referencedNode->save();
  }

  /**
   * Tests normalizing a populated entity reference custom field value.
   */
  public function testNormalizePopulatedReference(): void {
    $host = Node::create([
      'type' => 'custom_field_entity_test',
      'title' => 'Host node',
      'field_test' => [
        [
          'string' => 'value',
          'entity_reference' => $this->referencedNode->id(),
        ],
      ],
    ]);
    $host->save();
    $host = Node::load($host->id());

    $property = $host->get('field_test')->first()->get('entity_reference');
    $this->assertInstanceOf(CustomFieldEntityReference::class, $property);

    $normalized = $this->serializer->normalize($property, 'api_json');

    // Core 11.4 narrowed ComplexDataNormalizer::normalize() to `: array`; the
    // normalizer must return an array without raising a fatal error.
    $this->assertIsArray($normalized);
    // The normalizer exposes the referenced entity's UUID as the 'id' key.
    $this->assertSame($this->referencedNode->uuid(), $normalized['id']);
  }

  /**
   * Tests normalizing an empty entity reference custom field value.
   */
  public function testNormalizeEmptyReference(): void {
    // Populate a different subfield so the field item itself is not empty,
    // while leaving the entity reference subfield without a value.
    $host = Node::create([
      'type' => 'custom_field_entity_test',
      'title' => 'Host node',
      'field_test' => [
        [
          'string' => 'value',
        ],
      ],
    ]);
    $host->save();
    $host = Node::load($host->id());

    $property = $host->get('field_test')->first()->get('entity_reference');
    $this->assertInstanceOf(CustomFieldEntityReference::class, $property);

    $normalized = $this->serializer->normalize($property, 'api_json');

    // An empty reference previously returned NULL, which violates the core 11.4
    // `: array` contract. It must now return an empty array instead.
    $this->assertSame([], $normalized);
  }

}
