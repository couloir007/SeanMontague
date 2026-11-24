<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The "custom_field_datetime" data type.
 */
#[DataType(
  id: 'custom_field_datetime',
  label: new TranslatableMarkup('Datetime'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldDatetime extends CustomFieldDataTypeBase implements DateTimeInterface {

  /**
   * {@inheritdoc}
   */
  public function getDateTime(): ?DrupalDateTime {
    if ($this->value) {
      if (is_array($this->value)) {
        // Data of this type must always be stored in UTC.
        $datetime = DrupalDateTime::createFromArray($this->value, 'UTC');
      }
      else {
        // Data of this type must always be stored in UTC.
        $datetime = new DrupalDateTime($this->value, 'UTC');
      }
      return $datetime;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDateTime(DrupalDateTime $dateTime, bool $notify = TRUE): void {
    $this->value = $dateTime->format('c');
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE): void {
    $parent = $this->getParent();
    if (isset($value) && !is_array($value)) {
      $value = ['value' => $value];
    }
    if (isset($value['timezone'])) {
      $parent->set($this->getName() . CustomItem::SEPARATOR . 'timezone', $value['timezone']);
    }
    $this->value = !empty($value['value']) && is_string($value['value']) ? $value['value'] : NULL;
  }

  /**
   * Gets the stored timezone.
   *
   * @return string|null
   *   The stored timezone.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getTimezone(): ?string {
    $timezone = $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'timezone')->getValue();
    return $timezone ? (string) $timezone : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->value;
  }

}
