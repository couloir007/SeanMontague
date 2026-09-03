<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'time_range' custom field type.
 */
#[CustomFieldType(
  id: 'time_range',
  label: new TranslatableMarkup('Time range'),
  description: new TranslatableMarkup('A field containing a Time range.'),
  category: new TranslatableMarkup('Date/Time'),
  default_widget: 'time_range',
  default_formatter: 'time_range_default',
)]
class TimeRangeType extends TimeType {

  use TypedDataTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    $columns = parent::schema($settings);
    ['name' => $name] = $settings;
    $columns[$name]['description'] = 'The start time';
    $columns[$name . self::SEPARATOR . 'end'] = [
      'description' => t('The end time'),
    ] + $columns[$name];
    $columns[$name . self::SEPARATOR . 'duration'] = [
      'type' => 'int',
      'unsigned' => TRUE,
      'description' => 'The difference between start and end times in seconds.',
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    $properties = [];
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('custom_field_time_range')
      ->setLabel(new TranslatableMarkup('@name start', ['@name' => $name]))
      ->setDescription(new TranslatableMarkup('Seconds passed through midnight'))
      ->setSetting('unsigned', TRUE);

    $properties[$name . self::SEPARATOR . 'end'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('@name end', ['@name' => $name]))
      ->setDescription(new TranslatableMarkup('Seconds passed through midnight'))
      ->setSetting('unsigned', TRUE)
      ->setInternal(TRUE);

    $properties[$name . self::SEPARATOR . 'duration'] = DataDefinition::create('integer')
      ->setLabel(t('Duration, in seconds'))
      ->setDescription(t('The difference between start and end times in seconds.'))
      ->setInternal(TRUE)
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    /** @var array<string, mixed> $definition */
    $definition = $this->pluginDefinition;
    return $definition['constraints'];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): array {
    // The end time must never wrap past midnight — TimeRangeWidget's
    // start/end validation (and preSave()) rejects an end timestamp that's
    // numerically before the start. Cap the start so that even the maximum
    // duration keeps 'end' within the same day.
    $max_duration = 4 * 3600;
    $latest_start = 86400 - $max_duration - 1;
    $start = rand(0, $latest_start);
    // Normalize to whole minutes for a clean HH:MM:00 value.
    $start -= $start % 60;

    $duration = rand(1, 4) * 3600;
    $end = $start + $duration;

    return [
      'value' => $start,
      'end_value' => $end,
    ];
  }

}
