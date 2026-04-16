<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_options\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the Schema.org options.
 *
 * @group schemadotorg
 */
class SchemaDotOrgOptionsKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);
  }

  /**
   * Test Schema.org options.
   */
  public function testOptions(): void {
    $this->appendSchemaTypeDefaultProperties('Person', ['gender']);
    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Recipe');
    $this->createSchemaEntity('node', 'MedicalStudy');

    // Check that gender is assigned custom allowed values.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = FieldStorageConfig::load('node.schema_gender');
    $expected_allowed_values = [
      'male' => 'Male',
      'female' => 'Female',
      'unspecified' => 'Unspecified',
    ];
    $this->assertEquals($expected_allowed_values, $field_storage->getSetting('allowed_values'));

    // Check that knowsLanguage is assigned an allowed values function.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = FieldStorageConfig::load('node.schema_knows_language');
    $this->assertEquals('schemadotorg_options_allowed_values_language', $field_storage->getSetting('allowed_values_function'));

    // Check that suitableForDiet is assigned an allowed values function.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = FieldStorageConfig::load('node.schema_suitable_for_diet');
    $expected_allowed_values = [
      'diabetic_diet' => 'Diabetic',
      'gluten_free_diet' => 'Gluten Free',
      'halal_diet' => 'Halal',
      'hindu_diet' => 'Hindu',
      'kosher_diet' => 'Kosher',
      'low_calorie_diet' => 'Low Calorie',
      'low_fat_diet' => 'Low Fat',
      'low_lactose_diet' => 'Low Lactose',
      'low_salt_diet' => 'Low Salt',
      'vegan_diet' => 'Vegan',
      'vegetarian_diet' => 'Vegetarian',
    ];
    $this->assertEquals($expected_allowed_values, $field_storage->getSetting('allowed_values'));

    // Check that status allowed values use OptGroup for multiple enumerations.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = FieldStorageConfig::load('node.schema_status');
    $expected_allowed_values = [
      'event_cancelled' => 'Event Cancelled',
      'event_moved_online' => 'Event Moved Online',
      'event_postponed' => 'Event Postponed',
      'event_rescheduled' => 'Event Rescheduled',
      'event_scheduled' => 'Event Scheduled',
      'active_not_recruiting' => 'Active not Recruiting',
      'completed' => 'Completed',
      'enrolling_by_invitation' => 'Enrolling by Invitation',
      'not_yet_recruiting' => 'Not Yet Recruiting',
      'recruiting' => 'Recruiting',
      'results_available' => 'Results Available',
      'results_not_available' => 'Results not Available',
      'suspended' => 'Suspended',
      'terminated' => 'Terminated',
      'withdrawn' => 'Withdrawn',
    ];
    $this->assertEquals($expected_allowed_values, $field_storage->getSetting('allowed_values'));

    /* ********************************************************************** */
    // Check hook_schemadotorg_property_field_type_alter().
    /* ********************************************************************** */

    // Check default field type for Schema.org properties with allowed values.
    $field_types = ['string' => 'string'];
    schemadotorg_options_schemadotorg_property_field_type_alter($field_types, 'node', 'Person', 'gender');
    $this->assertEquals(['list_string' => 'list_string', 'string' => 'string'], $field_types);

    // Check that the property's field type if a default field type is defined.
    $field_types = ['string' => 'string'];
    schemadotorg_options_schemadotorg_property_field_type_alter($field_types, 'node', 'SpecialAnnouncement', 'category');
    $this->assertEquals(['list_string' => 'list_string', 'string' => 'string'], $field_types);

    // Check that the property's field type if a default field type is defined.
    $field_types = ['string' => 'string'];
    schemadotorg_options_schemadotorg_property_field_type_alter($field_types, 'node', 'Recommendation', 'category');
    $this->assertEquals(['list_string' => 'list_string', 'string' => 'string'], $field_types);

    // Check settings default field type to list string for
    // allowed values function.
    $field_types = ['string' => 'string'];
    schemadotorg_options_schemadotorg_property_field_type_alter($field_types, 'node', 'Person', 'knowsLanguage');
    $this->assertEquals(['list_string' => 'list_string', 'string' => 'string'], $field_types);

    // Check that the event attendance mode uses the aliased values.
    $this->appendSchemaTypeDefaultProperties('Event', ['eventAttendanceMode']);
    $this->createSchemaEntity('node', 'Event');
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = FieldStorageConfig::load('node.schema_event_attendance_mode');
    $expected_allowed_values = [
      'mixed' => 'Mixed',
      'offline' => 'Offline',
      'online' => 'Online',
    ];
    $this->assertEquals($expected_allowed_values, $field_storage->getSetting('allowed_values'));
  }

}
