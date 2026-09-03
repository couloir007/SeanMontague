<?php

namespace Drupal\geolocation_gpx\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Route entity.
 *
 * @ingroup geolocation_gpx
 *
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxLink> $link
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint> $route_points
 */
#[ContentEntityType(
  id: 'geolocation_gpx_route',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX Route'),
  entity_keys: ['id' => 'id', 'uuid' => 'uuid'],
  handlers: ['views_data' => 'Drupal\views\EntityViewsData'],
  base_table: 'geolocation_gpx_route'
)]
class GeolocationGpxRoute extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Route entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Route entity.'))
      ->setReadOnly(TRUE);

    $fields['route_points'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Route Point'))
      ->setDescription(t('A list of route points.'))
      ->setSetting('target_type', 'geolocation_gpx_waypoint')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

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

    $fields['number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Number'))
      ->setDescription(t('GPS route number.'));

    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Type'))
      ->setDescription(t('Type (classification) of route.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities): void {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxRoute $route */
    foreach ($entities as $route) {

      foreach ($route->link as $link) {
        $link->entity?->delete();
      }

      foreach ($route->route_points as $waypoint) {
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
    return $this->get('route_points')->referencedEntities();
  }

}
