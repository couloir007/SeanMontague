<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_embedded_content\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg_jsonld\Kernel\SchemaDotOrgJsonLdKernelTestBase;

/**
 * Tests the functionality of the Schema.org Embedded Content JSON-LD.
 *
 * @covers \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase
 *
 * @group schemadotorg
 */
class SchemaDotOrgEmbeddedContentJsonLdKernelTest extends SchemaDotOrgJsonLdKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sdc',
    'embedded_content',
    'schemadotorg_embedded_content',
    'schemadotorg_embedded_content_test',
  ];

  /**
   * Test Schema.org Blueprints embedded content JSON-LD.
   */
  public function testJsonLd(): void {
    $this->createSchemaEntity('node', 'WebPage');
    $node = Node::create([
      'type' => 'page',
      'title' => 'page',
      'body' => [
        'value' => '<embedded-content data-plugin-id="schemadotorg_thing" data-plugin-config="{&quot;name&quot;:&quot;Drupal.org&quot;,&quot;description&quot;:&quot;Drupal is an open source platform for building amazing digital experiences. It\\\u0027s made by a dedicated community. Anyone can use it, and it will always be free.&quot;,&quot;url&quot;:&quot;https:\/\/drupal.org&quot;}">&nbsp;</embedded-content>',
      ],
    ]);
    $node->save();

    /* ********************************************************************** */

    // Check Thing JSON-LD.
    $expected_result = [
      '@context' => 'https://schema.org',
      '@type' => 'Thing',
      'name' => 'Drupal.org',
      'description' => 'Drupal is an open source platform for building amazing digital experiences. It\\u0027s made by a dedicated community. Anyone can use it, and it will always be free.',
      'url' => 'https://drupal.org',
    ];
    $route_match = $this->manager->getEntityRouteMatch($node);
    $this->assertEquals($expected_result, $this->builder->build($route_match));

    // Check subtype Event JSON-LD.
    $node->body->value = '<embedded-content data-plugin-id="schemadotorg_thing" data-plugin-config="{&quot;subtype&quot;:&quot;Event&quot;,&quot;name&quot;:&quot;Drupal.org&quot;,&quot;description&quot;:&quot;Drupal is an open source platform for building amazing digital experiences. It\\\u0027s made by a dedicated community. Anyone can use it, and it will always be free.&quot;,&quot;url&quot;:&quot;https:\/\/drupal.org&quot;}">&nbsp;</embedded-content>';
    $node->save();
    $expected_result = [
      '@context' => 'https://schema.org',
      '@type' => 'Event',
      'name' => 'Drupal.org',
      'description' => 'Drupal is an open source platform for building amazing digital experiences. It\\u0027s made by a dedicated community. Anyone can use it, and it will always be free.',
      'url' => 'https://drupal.org',
    ];
    $route_match = $this->manager->getEntityRouteMatch($node);
    $this->assertEquals($expected_result, $this->builder->build($route_match));
  }

}
