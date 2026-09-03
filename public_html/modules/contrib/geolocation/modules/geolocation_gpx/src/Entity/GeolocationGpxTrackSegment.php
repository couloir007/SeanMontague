<?php

namespace Drupal\geolocation_gpx\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Track Segment entity.
 *
 * @ingroup geolocation_gpx
 *
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint> $track_points
 */
#[ContentEntityType(
  id: 'geolocation_gpx_track_segment',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX Track Segment'),
  entity_keys: ['id' => 'id', 'uuid' => 'uuid'],
  handlers: ['views_data' => 'Drupal\views\EntityViewsData'],
  base_table: 'geolocation_gpx_track_segment'
)]
class GeolocationGpxTrackSegment extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Track Segment entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Track Segment entity.'))
      ->setReadOnly(TRUE);

    $fields['track_points'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Track Point'))
      ->setDescription(t('A Track Point holds the coordinates, elevation, timestamp, and metadata for a single point in a track.'))
      ->setSetting('target_type', 'geolocation_gpx_waypoint')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities): void {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxTrackSegment $segment */
    foreach ($entities as $segment) {

      foreach ($segment->track_points as $waypoint) {
        $waypoint->entity?->delete();
      }
    }
    parent::preDelete($storage, $entities);
  }

  /**
   * Get waypoints.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint[]
   *   Waypoints.
   */
  public function getWaypoints(): array {
    return $this->get('track_points')->referencedEntities();
  }

}
