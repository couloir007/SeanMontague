<?php

namespace Drupal\geolocation_gpx\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Waypoint entity.
 *
 * @ingroup geolocation_gpx
 *
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxLink> $link
 */
#[ContentEntityType(
  id: 'geolocation_gpx_waypoint',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX Waypoint'),
  entity_keys: ['id' => 'id', 'uuid' => 'uuid'],
  handlers: ['views_data' => 'Drupal\views\EntityViewsData'],
  base_table: 'geolocation_gpx_waypoint'
)]
class GeolocationGpxWaypoint extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Waypoint entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Waypoint entity.'))
      ->setReadOnly(TRUE);

    $fields['latitude'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Latitude'))
      ->setDescription(t('The latitude of the point. This is always in decimal degrees, and always in WGS84 datum.'))
      ->setRequired(TRUE);

    $fields['longitude'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Longitude'))
      ->setDescription(t('The longitude of the point. This is always in decimal degrees, and always in WGS84 datum.'))
      ->setRequired(TRUE);

    $fields['elevation'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Elevation'))
      ->setDescription(t('Elevation (in meters) of the point.'));

    $fields['time'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Time'))
      ->setDescription(t('Creation/modification timestamp for element. Date and time in are in Univeral Coordinated Time (UTC), not local time! Conforms to ISO 8601 specification for date/time representation. Fractional seconds are allowed for millisecond timing in tracklogs.'));

    $fields['magnetic_variation'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Magnetic Variation'))
      ->setDescription(t('Magnetic variation (in degrees) at the point.'));

    $fields['geoidheight'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Geoid Height'))
      ->setDescription(t('Height (in meters) of geoid (mean sea level) above WGS84 earth ellipsoid.  As defined in NMEA GGA message.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('GPS name of route.'))
      ->setTranslatable(TRUE);

    $fields['comment'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Comment'))
      ->setDescription(t('GPS comment for route.'))
      ->setTranslatable(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Text description of route for user. Not sent to GPS.'))
      ->setTranslatable(TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('Source of data. Included to give user some idea of reliability and accuracy of data.'))
      ->setTranslatable(TRUE);

    $fields['link'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Links'))
      ->setDescription(t('Links to external information about the route.'))
      ->setSetting('target_type', 'geolocation_gpx_link')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['symbol'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Symbol'))
      ->setDescription(t('Text of GPS symbol name. For interchange with other programs, use the exact spelling of the symbol as displayed on the GPS.  If the GPS abbreviates words, spell them out.'));

    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Type'))
      ->setDescription(t('GPS route number.'));

    $fields['satellites'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Satellites'))
      ->setDescription(t('Number of satellites used to calculate the GPX fix.'));

    $fields['horizontal_dilution'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Horizontal dilution'))
      ->setDescription(t('Horizontal dilution of precision.'));

    $fields['vertical_dilution'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Vertical dilution'))
      ->setDescription(t('Vertical dilution of precision.'));

    $fields['position_dilution'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Position dilution'))
      ->setDescription(t('Position dilution of precision.'));

    $fields['age_of_dgps_data'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Age of DGPS data'))
      ->setDescription(t('Number of seconds since last DGPS update.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities): void {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint $waypoint */
    foreach ($entities as $waypoint) {
      foreach ($waypoint->link as $link) {
        $link->entity?->delete();
      }
    }

    parent::preDelete($storage, $entities);
  }

  /**
   * Get name to display for element.
   *
   * @return string
   *   Name.
   */
  public function getDisplayName(): string {
    return $this->name->value ?? '';
  }

  /**
   * Get latitude.
   *
   * @return float|null
   *   Latitude.
   */
  public function getLatitude(): ?float {
    $latitude = $this->latitude->value ?? NULL;

    return $latitude ? (float) $latitude : NULL;
  }

  /**
   * Get Longitude.
   *
   * @return float|null
   *   Longitude.
   */
  public function getLongitude(): ?float {
    $longitude = $this->longitude->value ?? NULL;

    return $longitude ? (float) $longitude : NULL;
  }

  /**
   * Get elevation.
   *
   * @return float|null
   *   Elevation.
   */
  public function getElevation(): ?float {
    $elevation = $this->elevation->value ?? NULL;

    return $elevation ? (float) $elevation : NULL;
  }

  /**
   * Get formatted time.
   *
   * @param string $format
   *   Format.
   *
   * @return string|null
   *   Time.
   */
  public function getFormattedTime(string $format = 'd.m.Y H:i:s'): ?string {
    /** @var \DateTime|NULL $datetime */
    $datetime = $this->time->value ?? NULL;
    if (!$datetime) {
      return NULL;
    }

    return $datetime->format($format);
  }

}
