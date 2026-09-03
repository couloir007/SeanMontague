<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The "custom_field_image" data type.
 *
 * The "custom_field_image" data type provides a way to process entity and
 * additional metadata as part of values.
 */
#[DataType(
  id: 'custom_field_image',
  label: new TranslatableMarkup('Image'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldImage extends CustomFieldEntityReferenceBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function setValue($value, $notify = TRUE): void {
    $entity = $value['entity'] ?? NULL;
    // Drupal core Default Content API Importer will call this method with an
    // array of exported entity reference data. If this is the case, try loading
    // the referenced entity here, before assigning as data value.
    if (!$entity && is_string($value) && Uuid::isValid($value)) {
      $entity = $this->getEntityByUuid($value);
    }
    if (!is_array($value)) {
      $value = ['target_id' => $value];
    }
    if (isset($value['alt'])) {
      $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'alt', $value['alt']);
    }
    if (isset($value['title'])) {
      $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'title', $value['title']);
    }
    if (isset($value['width'])) {
      $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'width', $value['width']);
    }
    if (isset($value['height'])) {
      $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'height', $value['height']);
    }
    if ($entity instanceof EntityInterface) {
      if ($entity->isNew()) {
        try {
          $entity->save();
        }
        catch (EntityStorageException $exception) {
          $entity = NULL;
        }
      }
      $value['target_id'] = $entity->id();
    }

    // Invalidate the cached entity whenever the target changes, so a stale
    // reference can't be returned for a new target_id. It will be lazily
    // reloaded on next getEntity() call if needed.
    if (($value['target_id'] ?? NULL) !== $this->value) {
      $this->entity = $entity instanceof EntityInterface ? $entity : NULL;
    }

    $this->value = $value['target_id'];
  }

  /**
   * Returns the alt value.
   *
   * @return string|null
   *   The image alt text.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getAlt(): ?string {
    return $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'alt')->getValue();
  }

  /**
   * Returns the title value.
   *
   * @return string|null
   *   The image title.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getTitle(): ?string {
    return $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'title')->getValue();
  }

  /**
   * Returns the width value.
   *
   * @return int|null
   *   The image width.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getWidth(): ?int {
    $width = $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'width')->getValue();
    return $width ? (int) $width : NULL;
  }

  /**
   * Returns the height value.
   *
   * @return int|null
   *   The image height.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getHeight(): ?int {
    $height = $this->getParent()->get($this->getName() . CustomItem::SEPARATOR . 'height')->getValue();
    return $height ? (int) $height : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    $value = $this->getValue();
    return (int) $value;
  }

}
