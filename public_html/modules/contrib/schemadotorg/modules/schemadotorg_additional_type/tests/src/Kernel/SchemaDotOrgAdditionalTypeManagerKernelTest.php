<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_additional_type\Kernel;

use Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeManagerInterface;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;
use Drupal\Tests\schemadotorg_additional_type\Traits\SchemaDotOrgAdditionalTypeTestTrait;

/**
 * Tests Schema.org additional type manager.
 *
 * @group schemadotorg
 */
class SchemaDotOrgAdditionalTypeManagerKernelTest extends SchemaDotOrgEntityKernelTestBase {
  use SchemaDotOrgAdditionalTypeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_additional_type',
  ];

  /**
   * The Schema.org additional type manager.
   */
  protected SchemaDotOrgAdditionalTypeManagerInterface $additionalTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');

    $this->installConfig(['schemadotorg_additional_type']);

    $this->additionalTypeManager = $this->container->get('schemadotorg_additional_type.manager');
  }

  /**
   * Tests additional type manager.
   */
  public function testManager(): void {
    $mapping = $this->createSchemaEntity('node', 'Event');
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
    $field_storage_config = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->load("node.schema_event_type");

    // Check if the additional type is required for a given Schema.org type and bundle.
    $this->assertTrue($this->additionalTypeManager->isAdditionalTypeRequired('node', 'event', 'Event'));
    $this->assertTrue($this->additionalTypeManager->isAdditionalTypeRequired('node', NULL, 'Event'));
    $this->assertFalse($this->additionalTypeManager->isAdditionalTypeRequired('node', 'event', 'Thing'));

    // Determines if the additionalType property is required for a given content type.
    $this->assertTrue($this->additionalTypeManager->isNodeTypeAdditionalTypeRequired('event'));
    $this->assertFalse($this->additionalTypeManager->isNodeTypeAdditionalTypeRequired('not_event'));

    // Determines if the "additionalType" mapping field allows multiple values.
    $this->assertFalse($this->additionalTypeManager->isMappingAdditionalTypeMultiple($mapping));
    $field_storage_config->setCardinality(-1)->save();
    $this->assertTrue($this->additionalTypeManager->isMappingAdditionalTypeMultiple($mapping));
    $field_storage_config->setCardinality(1)->save();

    // Get the allowed values for the additional type field of a Schema.org mapping.
    $expected_allowed_values = [
      'business_event' => 'Business Event',
      'childrens_event' => 'Childrens Event',
      'comedy_event' => 'Comedy Event',
      'conference_event' => 'Conference Event',
      'course_instance' => 'Course Instance',
      'dance_event' => 'Dance Event',
      'delivery_event' => 'Delivery Event',
      'education_event' => 'Education Event',
      'event_series' => 'Event Series',
      'exhibition_event' => 'Exhibition Event',
      'festival' => 'Festival',
      'food_event' => 'Food Event',
      'hackathon' => 'Hackathon',
      'literary_event' => 'Literary Event',
      'music_event' => 'Music Event',
      'publication_event' => 'Publication Event',
      'broadcast_event' => '- Broadcast Event',
      'on_demand_event' => '- On Demand Event',
      'performing_arts_event' => 'Performing Arts Event',
      'sale_event' => 'Sale Event',
      'screening_event' => 'Screening Event',
      'social_event' => 'Social Event',
      'sports_event' => 'Sports Event',
      'theater_event' => 'Theater Event',
      'visual_arts_event' => 'Visual Arts Event',
    ];
    $allowed_values = $this->additionalTypeManager->getMappingAdditionalTypeAllowedValues($mapping);
    $this->assertEquals($expected_allowed_values, $allowed_values);

  }

}
