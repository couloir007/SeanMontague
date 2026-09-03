<?php

namespace Drupal\Tests\geolocation\Kernel;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the geolocation field type.
 *
 * @group geolocation
 */
class GeolocationItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['geolocation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a geolocation field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'geolocation',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test',
      'label' => 'Geolocation',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the geolocation field type.
   */
  public function testGeolocationItem(): void {
    $entityTestStorage = \Drupal::entityTypeManager()->getStorage('entity_test');
    $lat = 49.880657;
    $lng = 10.869212;
    $data = 'Foo bar';

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entityTestStorage->create([
      'title' => $this->randomMachineName(),
      'field_test' => [
        'lat' => $lat,
        'lng' => $lng,
        'data' => $data,
      ],
    ]);
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entityTestStorage->load($id);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->get('field_test')[0], 'Field item implements interface.');

    /** @var \Drupal\geolocation\GeolocationItemListInterface $field_item */
    $field_item = $entity->get('field_test');

    $this->assertEqualsWithDelta($lat, (float) $field_item->lat, 0.00001, "Lat $field_item->lat is equal to lat $lat.");
    $this->assertEqualsWithDelta($lat, (float) $field_item[0]->lat, 0.00001, "Lat {$field_item[0]->lat} is equal to lat $lat.");
    $this->assertEqualsWithDelta($lng, (float) $field_item->lng, 0.00001, "Lng $field_item->lng is equal to lng $lng.");
    $this->assertEqualsWithDelta($lng, (float) $field_item[0]->lng, 0.00001, "Lng {$field_item[0]->lng} is equal to lng $lng.");

    $this->assertEquals(round($field_item->lat_sin, 5), round(sin(deg2rad($lat)), 5), "Sine for latitude calculated correctly.");
    $this->assertEquals(round($field_item->lat_cos, 5), round(cos(deg2rad($lat)), 5), "Cosine for latitude calculated correctly.");
    $this->assertEquals(round($field_item->lng_rad, 5), round(deg2rad($lng), 5), "Radian value for longitude calculated correctly.");

    $this->assertEquals($field_item->data, $data, "Data $field_item->data is equal to data $data.");

    // Verify changing the field value.
    $new_lat = rand(-90, 90) - rand(0, 999999) / 1000000;
    $new_lng = rand(-180, 180) - rand(0, 999999) / 1000000;
    $new_data = ['an_array'];
    $field_item->lat = $new_lat;
    $field_item->lng = $new_lng;
    $field_item->data = $new_data;

    // Assert that the calculated properties were updated.
    $this->assertEquals(round($field_item->lat_sin, 5), round(sin(deg2rad($new_lat)), 5), "Sine for latitude calculated correctly after change.");
    $this->assertEquals(round($field_item->lat_cos, 5), round(cos(deg2rad($new_lat)), 5), "Cosine for latitude calculated correctly after change.");
    $this->assertEquals(round($field_item->lng_rad, 5), round(deg2rad($new_lng), 5), "Radian value for longitude calculated correctly after change.");

    // Do the same by setting the values as an array.
    $entityTestStorage->resetCache([$id]);

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entityTestStorage->load($id);

    $entity->set('field_test', [
      'lat' => $new_lat,
      'lng' => $new_lng,
      'data' => $new_data,
    ]);

    /** @var \Drupal\geolocation\GeolocationItemListInterface $field_item */
    $field_item = $entity->get('field_test');

    $this->assertEqualsWithDelta($new_lat, (float) $field_item->lat, 0.00001, "Lat $field_item->lat is equal to new lat $new_lat.");
    $this->assertEqualsWithDelta($new_lng, (float) $field_item->lng, 0.00001, "Lng $field_item->lng is equal to new lng $new_lng.");
    $this->assertEquals($field_item->data, $new_data, "Data is correctly updated to new data.");

    // Assert that the calculated properties were updated.
    $this->assertEquals(round($field_item->lat_sin, 5), round(sin(deg2rad($new_lat)), 5), "Sine for latitude calculated correctly after change.");
    $this->assertEquals(round($field_item->lat_cos, 5), round(cos(deg2rad($new_lat)), 5), "Cosine for latitude calculated correctly after change.");
    $this->assertEquals(round($field_item->lng_rad, 5), round(deg2rad($new_lng), 5), "Radian value for longitude calculated correctly after change.");

    // Read changed entity and assert changed values.
    $entity->save();

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entityTestStorage->load($id);

    /** @var \Drupal\geolocation\GeolocationItemListInterface $field_item */
    $field_item = $entity->get('field_test');

    $this->assertEqualsWithDelta($new_lat, (float) $field_item->lat, 0.00001, "Lat $field_item->lat is equal to new lat $new_lat.");
    $this->assertEqualsWithDelta($new_lng, (float) $field_item->lng, 0.00001, "Lng $field_item->lng is equal to new lng $new_lng.");

    $this->assertEquals(round($field_item->lat_sin, 5), round(sin(deg2rad($new_lat)), 5), "Sine for latitude calculated correctly after change.");
    $this->assertEquals(round($field_item->lat_cos, 5), round(cos(deg2rad($new_lat)), 5), "Cosine for latitude calculated correctly after change.");
    $this->assertEquals(round($field_item->lng_rad, 5), round(deg2rad($new_lng), 5), "Radian value for longitude calculated correctly after change.");

    $this->assertEquals($field_item->data, $new_data, "Data is correctly updated to new data.");
  }

}
