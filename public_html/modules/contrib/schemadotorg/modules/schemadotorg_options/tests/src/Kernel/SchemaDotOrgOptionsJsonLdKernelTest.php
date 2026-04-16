<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_webpage\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg_jsonld\Kernel\SchemaDotOrgJsonLdKernelTestBase;

/**
 * Tests the functionality of the Schema.org Options JSON-LD.
 *
 * @covers schemadotorg_options_schemadotorg_jsonld_schema_property_alter
 * @group schemadotorg
 */
class SchemaDotOrgOptionsJsonLdKernelTest extends SchemaDotOrgJsonLdKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_options']);
    $this->manager = $this->container->get('schemadotorg_jsonld.manager');
  }

  /**
   * Test Schema.org Options.
   */
  public function testOptions(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    /* ********************************************************************** */

    $this->createSchemaEntity('node', 'SpecialAnnouncement');

    $node = Node::create([
      'type' => 'special_announcement',
      'title' => 'Some announcement',
      'schema_category' => 'emergency',
    ]);
    $node->save();

    // Check that the JSON-LD for a category list element displays
    // the WikiData URI.
    $jsonld = $this->builder->buildEntity($node);
    $this->assertEquals('SpecialAnnouncement', $jsonld['@type']);
    $this->assertEquals('https://www.wikidata.org/wiki/Q5070802', $jsonld['category']);

    // Check that the JSON-LD for a category list element displays
    // the option text.
    $this->config('schemadotorg_options.settings')
      ->set('jsonld_uris', [])
      ->set('jsonld_text', ['SpecialAnnouncement--category'])
      ->save();
    $jsonld = $this->builder->buildEntity($node);
    $this->assertEquals('SpecialAnnouncement', $jsonld['@type']);
    $this->assertEquals('Emergency', $jsonld['category']);

    /* ********************************************************************** */

    $this->createSchemaEntity('node', 'Recipe');

    $node = Node::create([
      'type' => 'recipe',
      'title' => 'Some recipe',
      'schema_suitable_for_diet' => 'diabetic_diet',
    ]);
    $node->save();

    // Check that the snake case enumeration value is converted back to camel case.
    $jsonld = $this->builder->buildEntity($node);
    $this->assertEquals(['https://schema.org/DiabeticDiet'], $jsonld['suitableForDiet']);

    /* ********************************************************************** */

    $this->appendSchemaTypeDefaultProperties('Event', ['eventAttendanceMode']);
    $this->createSchemaEntity('node', 'Event');

    $node = Node::create([
      'type' => 'event',
      'title' => 'Some event',
      'schema_event_attendance_mode' => 'mixed',
    ]);
    $node->save();

    // Check that the alias value is converted to an enumeration value.
    $jsonld = $this->builder->buildEntity($node);
    $this->assertEquals('https://schema.org/MixedEventAttendanceMode', $jsonld['eventAttendanceMode']);
  }

}
