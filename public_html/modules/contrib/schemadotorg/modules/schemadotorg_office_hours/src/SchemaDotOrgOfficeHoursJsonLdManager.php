<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_office_hours;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * The Schema.org office hours JSON-LD manager.
 */
class SchemaDotOrgOfficeHoursJsonLdManager implements SchemaDotOrgOfficeHoursJsonLdManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void {
    $field_type = $item->getFieldDefinition()->getType();
    if ($field_type !== 'office_hours') {
      return;
    }

    $schema_days = [
      'https://schema.org/Sunday',
      'https://schema.org/Monday',
      'https://schema.org/Tuesday',
      'https://schema.org/Wednesday',
      'https://schema.org/Thursday',
      'https://schema.org/Friday',
      'https://schema.org/Saturday',
    ];

    $value = ['@type' => 'OpeningHoursSpecification'];
    if (isset($schema_days[$item->day])) {
      $value['dayOfWeek'] = $schema_days[$item->day];
    }
    if ($item->starthours) {
      $value['opens'] = preg_replace('/(\d\d)$/', ':$1', (string) $item->starthours);
    }
    if ($item->endhours) {
      $value['closes'] = preg_replace('/(\d\d)$/', ':$1', (string) $item->endhours);
    }
    if ($item->comment) {
      $value['description'] = $item->comment;
    }
  }

}
