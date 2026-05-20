<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_physical;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\physical\Plugin\Field\FieldType\DimensionsItem;
use Drupal\physical\Plugin\Field\FieldType\MeasurementItem;

/**
 * The Schema.org physical JSON-LD manager.
 */
class SchemaDotOrgPhysicalJsonLdManager implements SchemaDotOrgPhysicalJsonLdManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void {
    if ($item instanceof MeasurementItem) {
      $value = [
        '@type' => 'QuantitativeValue',
        'value' => $item->number,
        'unitText' => $item->unit,
      ];
    }
    elseif ($item instanceof DimensionsItem) {
      $value = [];
      $dimension_properties = ['length', 'height', 'width'];
      foreach ($dimension_properties as $dimension_property) {
        $value[] = [
          '@type' => 'QuantitativeValue',
          'name' => $dimension_property,
          'value' => $item->{$dimension_property},
          'unitText' => $item->unit,
        ];
      }
    }
  }

}
