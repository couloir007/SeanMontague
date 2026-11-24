<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\Time;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The custom_field_time_range data type.
 */
#[DataType(
  id: 'custom_field_time_range',
  label: new TranslatableMarkup('Time range'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldTimeRange extends CustomFieldDataTypeBase {

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE): void {
    // Treat the values as property value of the main property, if no array is
    // given.
    $parent = $this->getParent();
    if (isset($value) && !is_array($value)) {
      $value = [
        'value' => $value,
        'end' => NULL,
      ];
    }

    $this->value = $value['value'] ?? NULL;
    $end_time = $value['end'] ?? NULL;
    if (!Time::isEmpty($this->value) && !Time::isEmpty($end_time)) {
      $parent->set($this->getName() . CustomItem::SEPARATOR . 'end', $end_time);
    }
  }

  /**
   * Gets the duration.
   *
   * @return int|null
   *   The duration value in seconds.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getDuration(): ?int {
    $duration = $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'duration')->getValue();
    return $duration ? (int) $duration : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->value;
  }

}
