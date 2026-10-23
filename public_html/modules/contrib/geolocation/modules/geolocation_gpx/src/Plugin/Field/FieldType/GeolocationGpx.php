<?php

namespace Drupal\geolocation_gpx\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'geolocation_gpx' field type.
 *
 * @property ?int $gpx_id
 *   GPX ID.
 * @property ?int $gpx_file_id
 *   GPX file ID.
 * @property ?bool $gpx_owned
 *   Whether removing this item deletes the referenced GPX.
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
        'gpx_owned' => [
          'description' => 'Whether removing this item deletes the referenced GPX. Nullable, as items written before this column existed - and any written programmatically - predate the choice and are treated as owning.',
          'type' => 'int',
          'size' => 'tiny',
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

    $properties['gpx_file_id'] = DataDefinition::create('integer')
      ->setLabel(t('GPX File'));

    $properties['gpx_owned'] = DataDefinition::create('boolean')
      ->setLabel(t('GPX owned'));

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

    $gpx_id = $this->gpx_id;

    if ($gpx_id && $this->isOwning()) {
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

  /**
   * Whether this item owns the GPX it points to.
   *
   * Only an explicit opt-out releases the GPX. Anything else - a referenced
   * GPX the user declared a dependency on, an imported one, or a value stored
   * before this column existed - is owned, so the GPX goes when the item does.
   *
   * @return bool
   *   TRUE if removing this item must delete the referenced GPX.
   */
  public function isOwning(): bool {
    return (bool) ($this->gpx_owned ?? TRUE);
  }

}
