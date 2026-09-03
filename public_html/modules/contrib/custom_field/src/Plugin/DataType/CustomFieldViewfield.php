<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The "custom_field_viewfield" data type.
 */
#[DataType(
  id: 'custom_field_viewfield',
  label: new TranslatableMarkup('Viewfield'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldViewfield extends CustomFieldDataTypeBase {

  /**
   * The entity object or null.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected ?EntityInterface $entity = NULL;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function setValue($value, $notify = TRUE): void {
    $display_id = $value['display_id'] ?? NULL;
    $arguments = $value['arguments'] ?? NULL;
    $items_to_display = $value['items_to_display'] ?? NULL;
    if (!empty($display_id)) {
      $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'display', $display_id);
    }
    if (!empty($arguments)) {
      $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'arguments', $arguments);
    }
    if (!empty($items_to_display)) {
      $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'items', (int) $items_to_display);
    }

    $entity = $value['entity'] ?? NULL;
    if ($entity instanceof EntityInterface) {
      if ($entity->isNew()) {
        try {
          $entity->save();
        }
        catch (EntityStorageException $exception) {
          $entity = NULL;
        }
      }
      $value = $entity?->id();
    }

    $new_value = is_array($value) ? ($value['target_id'] ?? NULL) : $value;

    // Invalidate the cached entity whenever the target changes without an
    // explicit entity object being supplied, so a stale reference can't be
    // returned for a different target_id.
    if ($entity instanceof EntityInterface) {
      $this->entity = $entity;
    }
    elseif ($new_value !== $this->value) {
      $this->entity = NULL;
    }

    $this->value = $new_value;
  }

  /**
   * Helper function to load an entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntity(): ?EntityInterface {
    if (empty($this->entity) && !empty($this->value)) {
      $target_type = $this->getDataDefinition()->getSetting('target_type');
      $storage = \Drupal::entityTypeManager()->getStorage($target_type);
      $this->entity = $storage->load($this->getValue());
    }

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    $value = $this->getValue();
    return $value !== NULL ? (string) $value : NULL;
  }

}
