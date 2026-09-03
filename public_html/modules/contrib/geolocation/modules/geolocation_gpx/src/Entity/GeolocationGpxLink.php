<?php

namespace Drupal\geolocation_gpx\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Link entity.
 *
 * @ingroup geolocation_gpx
 */
#[ContentEntityType(
  id: 'geolocation_gpx_link',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX Link'),
  entity_keys: ['id' => 'id', 'uuid' => 'uuid'],
  handlers: ['views_data' => 'Drupal\views\EntityViewsData'],
  base_table: 'geolocation_gpx_link'
)]
class GeolocationGpxLink extends ContentEntityBase {

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

    $fields['href'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL'))
      ->setTranslatable(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Mime type of content (image/jpeg).'));

    $fields['text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Text'))
      ->setDescription(t('Text of hyperlink.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
