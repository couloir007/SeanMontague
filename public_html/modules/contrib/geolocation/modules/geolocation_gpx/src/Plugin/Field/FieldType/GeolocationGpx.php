<?php

namespace Drupal\geolocation_gpx\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'geolocation_gpx' field type.
 */
#[FieldType(id: 'geolocation_gpx',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX - File & Data'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('This field stores a GPX file and references the GPX Entity imported from it.'), category: 'geo_spatial', default_widget: 'geolocation_gpx_file', default_formatter: 'geolocation_gpx_table')]
class GeolocationGpx extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'gpx_id' => [
          'description' => 'GPX',
          'type' => 'int',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'gpx_file_id' => [
          'description' => 'GPX File',
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'gpx_id' => ['gpx_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['gpx_id'] = DataDefinition::create('integer')
      ->setLabel(t('GPX'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName(): string {
    return 'gpx_id';
  }

  /**
   * {@inheritdoc}
   */
  public function delete(): void {

    $gpx_id = $this->values['gpx_id'] ?? FALSE;

    if ($gpx_id) {
      try {
        /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx|NULL $gpx */
        $gpx = \Drupal::entityTypeManager()->getStorage('geolocation_gpx')->load(
          $gpx_id,
        );

        $gpx?->delete();
      }
      catch (\Exception $e) {
        \Drupal::logger('geolocation_gpx')->error('Could not delete GPX element. ' . $e->getMessage());
      }
    }

    parent::delete();
  }

}
