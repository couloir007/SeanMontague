<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The custom_field_link data type.
 */
#[DataType(
  id: 'custom_field_link',
  label: new TranslatableMarkup('Link'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldLink extends CustomFieldDataTypeBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function setValue($value, $notify = TRUE): void {
    // Treat the value as the main property value, if no array is given.
    $parent = $this->getParent();
    if ($value && !is_array($value)) {
      $value = [
        'uri' => $value,
      ];
    }
    if (isset($value['title'])) {
      $parent->set($this->getName() . CustomItem::SEPARATOR . 'title', $value['title']);
    }
    if (isset($value['options']) && is_array($value['options'])) {
      $parent->set($this->getName() . CustomItem::SEPARATOR . 'options', $value['options']);
    }

    $this->value = $value['uri'] ?? NULL;
  }

  /**
   * Returns the title value.
   *
   * @return string|null
   *   The link title.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getTitle(): ?string {
    $field_type = $this->getDataDefinition()->getSetting('field_type');
    if ($field_type === 'link') {
      return $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'title')->getValue();
    }
    return NULL;
  }

  /**
   * Returns the link options.
   *
   * @return array<string, mixed>
   *   The link options array.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getOptions(): array {
    $field_type = $this->getDataDefinition()->getSetting('field_type');
    if ($field_type === 'link') {
      return $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'options')->getValue() ?? [];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->value;
  }

}
