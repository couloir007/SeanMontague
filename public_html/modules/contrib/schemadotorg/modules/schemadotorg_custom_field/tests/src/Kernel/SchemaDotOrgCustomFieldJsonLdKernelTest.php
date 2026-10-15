<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_custom_field\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg_jsonld\Kernel\SchemaDotOrgJsonLdKernelTestBase;

/**
 * Tests the functionality of the Schema.org Custom Field JSON-LD .
 *
 * @covers \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldJsonLdManager
 * @group schemadotorg
 */
class SchemaDotOrgCustomFieldJsonLdKernelTest extends SchemaDotOrgJsonLdKernelTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the custom field module has a schema.
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
   * Test Schema.org Custom Field JSON-LD manager.
   */
  public function testCustomField(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    $this->createSchemaEntity('node', 'Recipe');

    $node = Node::create([
      'type' => 'recipe',
      'title' => 'Some recipe',
      'schema_nutrition' => [
        [
          'serving_size' => '{service}',
          'calories' => '10.00',
        ],
      ],
    ]);
    $node->save();

    /* ********************************************************************** */

    // Check that custom field data is included in Recipe JSON-LD with units.
    $route_match = $this->manager->getEntityRouteMatch($node);
    $jsonld = $this->builder->build($route_match);
    $expected_result_nutrition = [
      '@type' => 'NutritionInformation',
      'servingSize' => '{service}',
      'calories' => '10.00 calories',
    ];
    $this->assertEquals($expected_result_nutrition, $jsonld['nutrition']);

    /* ********************************************************************** */

    $mapping = $this->createSchemaEntity('node', 'Product', [
      'properties' => [
        'isSimilarTo' => [
          'name' => '_add_',
        ],
      ],
    ]);
    $field_name = $mapping->getSchemaPropertyFieldName('isSimilarTo');

    $node = Node::create([
      'type' => 'product',
      'title' => 'Example product',
      $field_name => [
        [
          'name' => 'Similar product',
          'description' => 'A functionally similar product.',
          'url' => 'https://example.com/similar-product',
          'additional_property' => [
            [
              'key' => 'Approx. Weight',
              'value' => '450',
            ],
            [
              'key' => 'Interface',
              'value' => 'USB',
            ],
          ],
        ],
      ],
    ]);
    $node->save();

    // Check that map custom field data is stored on the entity.
    $this->assertEquals([
      [
        'key' => 'Approx. Weight',
        'value' => '450',
      ],
      [
        'key' => 'Interface',
        'value' => 'USB',
      ],
    ], $node->get($field_name)->first()->get('additional_property')->getValue());

    // Check that map custom field data is included in Product JSON-LD.
    $route_match = $this->manager->getEntityRouteMatch($node);
    $jsonld = $this->builder->build($route_match);
    $expected_result_product = [
      '@type' => 'Product',
      'name' => 'Similar product',
      'description' => 'A functionally similar product.',
      'url' => 'https://example.com/similar-product',
      'additionalProperty' => [
        [
          '@type' => 'PropertyValue',
          'name' => 'Approx. Weight',
          'value' => '450',
        ],
        [
          '@type' => 'PropertyValue',
          'name' => 'Interface',
          'value' => 'USB',
        ],
      ],
    ];
    $this->assertEquals($expected_result_product, $jsonld['isSimilarTo']);
  }

}
