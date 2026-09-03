<?php

namespace Drupal\geolocation_geofield\Plugin\geolocation\DataProvider;

use Drupal\geolocation\Attribute\DataProvider;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Provides Google Maps.
 */
#[DataProvider(
  id: 'geofield',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geofield'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geofield.')
)]
class Geofield extends DataProviderBase implements DataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function isViewsGeoOption(FieldPluginBase $viewsField): bool {
    if (
      $viewsField instanceof EntityField
      && in_array($viewsField->getPluginId(), ['field', 'search_api_field'])
    ) {
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($viewsField->getEntityType());
      if (!empty($field_storage_definitions[$viewsField->field])) {
        $field_storage_definition = $field_storage_definitions[$viewsField->field];

        if ($field_storage_definition->getType() == 'geofield') {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool {
    return ($fieldDefinition->getType() == 'geofield');
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsFromItem(FieldItemInterface $fieldItem): array {
    if ($fieldItem instanceof GeofieldItem) {
      return [
        [
          '#type' => 'geolocation_map_location',
          '#coordinates' => [
            'lat' => $fieldItem->get('lat')->getValue(),
            'lng' => $fieldItem->get('lon')->getValue(),
          ],
        ],
      ];
    }

    return [];
  }

}
