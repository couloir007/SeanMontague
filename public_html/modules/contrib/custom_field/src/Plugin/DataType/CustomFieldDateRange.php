<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeTypeInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The custom_field_daterange data type.
 */
#[DataType(
  id: 'custom_field_daterange',
  label: new TranslatableMarkup('Date range'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldDateRange extends CustomFieldDataTypeBase {

  /**
   * Date format for SQL conversion.
   *
   * @var string
   *
   * @see \Drupal\views\Plugin\views\query\Sql::getDateFormat()
   */
  protected string $dateFormat = DateTimeTypeInterface::DATETIME_STORAGE_FORMAT;

  /**
   * The date type.
   *
   * @var string
   */
  protected string $datetimeType = DateTimeType::DATETIME_TYPE_DATETIME;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?FieldItemInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if ($definition->getSetting('datetime_type') === DateTimeType::DATETIME_TYPE_DATE) {
      $this->datetimeType = DateTimeType::DATETIME_TYPE_DATE;
      $this->dateFormat = DateTimeTypeInterface::DATE_STORAGE_FORMAT;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function setValue($value, $notify = TRUE): void {
    // Treat the values as property value of the main property, if no array is
    // given.
    $parent = $this->getParent();
    if (isset($value) && !is_array($value)) {
      $value = [
        'value' => $value,
        'end' => NULL,
        'timezone' => NULL,
      ];
    }

    $this->value = !empty($value['value']) && is_string($value['value']) ? $value['value'] : NULL;
    $end_date = !empty($value['end']) && is_string($value['end']) ? $value['end'] : NULL;
    if (!empty($this->value)) {
      if (!empty($end_date)) {
        $parent->set($this->getName() . CustomItem::SEPARATOR . 'end', $end_date);
      }
      if (isset($value['timezone'])) {
        $parent->set($this->getName() . CustomItem::SEPARATOR . 'timezone', (string) $value['timezone']);
      }
    }
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
