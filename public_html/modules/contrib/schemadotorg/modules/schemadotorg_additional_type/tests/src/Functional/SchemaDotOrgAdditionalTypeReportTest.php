<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_additional_type\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;
use Drupal\Tests\schemadotorg_additional_type\Traits\SchemaDotOrgAdditionalTypeTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the functionality of the Schema.org additional type report.
 *
 * @covers \Drupal\schemadotorg_additional_type\Controller\SchemaDotOrgAdditionalTypeReportController
 * @group schemadotorg
 */
class SchemaDotOrgAdditionalTypeReportTest extends SchemaDotOrgBrowserTestBase {
  use SchemaDotOrgAdditionalTypeTestTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_taxonomy',
    'schemadotorg_additional_type',
  ];

  /**
   * Test Schema.org additional type report.
   */
  public function testReport(): void {
    $assert = $this->assertSession();

    // Create place content type with amenity taxonomy field.
    $this->appendSchemaTypeDefaultProperties('Place', 'amenityFeature');
    $this->createSchemaEntity('node', 'Place');

    // Create event content type with location entity reference field.
    $this->appendSchemaTypeDefaultProperties('Event', 'location');
    $this->createSchemaEntity('node', 'Event');

    // Create Parent > Child taxonomy term relationships.
    $parent_term = Term::create(['name' => 'Parent term', 'vid' => 'amenity']);
    $parent_term->save();
    $child_term = Term::create(['name' => 'Child term', 'vid' => 'amenity', 'parent' => $parent_term->id()]);

    $child_term->save();

    $other_term = Term::create(['name' => 'Other term', 'vid' => 'amenity']);
    $other_term->save();

    // Create a 'Place' node.
    $place_node = $this->drupalCreateNode([
      'type' => 'place',
      'title' => 'Some Place',
    ]);

    $this->config('schemadotorg_additional_type.settings')
      ->set('default_field_values',
        [
          'node--place' => [
            'amenityFeature' => [(int) $child_term->id(), (int) $other_term->id()],
          ],
          'node--event' => [
            'title' => 'This is an event',
          ],
          'node--event--business_event' => [
            'title' => 'This is a business event',
            'schema_location' => (int) $place_node->id(),
          ],
        ],
      )
      ->save();

    $this->drupalLogin($this->createUser(['access site reports']));
    $this->drupalGet('/admin/reports/schemadotorg/docs/additional-types');

    // Check that term and entity references are displayed and linked as expected.x.
    $assert->responseContains($child_term->toLink('Parent term ﹥ Child term')->toString());
    $assert->responseContains($other_term->toLink('Other term')->toString());
    $assert->responseContains($place_node->toLink()->toString());

    $this->drupalGet('/admin/reports/schemadotorg/docs/additional-types/download');
    $expected_csv = <<<CSV
entity_type_id,bundle,schema_type,additional_type,amenityFeature,title,schema_location
node,event,Event,,,"'This is an event'",
node,event,Event,business_event,,"'This is a business event'",1
node,event,Event,childrens_event,,"'This is an event'",
node,event,Event,comedy_event,,"'This is an event'",
node,event,Event,conference_event,,"'This is an event'",
node,event,Event,course_instance,,"'This is an event'",
node,event,Event,dance_event,,"'This is an event'",
node,event,Event,delivery_event,,"'This is an event'",
node,event,Event,education_event,,"'This is an event'",
node,event,Event,event_series,,"'This is an event'",
node,event,Event,exhibition_event,,"'This is an event'",
node,event,Event,festival,,"'This is an event'",
node,event,Event,food_event,,"'This is an event'",
node,event,Event,hackathon,,"'This is an event'",
node,event,Event,literary_event,,"'This is an event'",
node,event,Event,music_event,,"'This is an event'",
node,event,Event,performing_arts_event,,"'This is an event'",
node,event,Event,publication_event,,"'This is an event'",
node,event,Event,broadcast_event,,"'This is an event'",
node,event,Event,on_demand_event,,"'This is an event'",
node,event,Event,sale_event,,"'This is an event'",
node,event,Event,screening_event,,"'This is an event'",
node,event,Event,social_event,,"'This is an event'",
node,event,Event,sports_event,,"'This is an event'",
node,event,Event,theater_event,,"'This is an event'",
node,event,Event,visual_arts_event,,"'This is an event'",
node,place,Place,,"- 2
- 3
",,
CSV;
    $assert->responseContains($expected_csv);
  }

}
