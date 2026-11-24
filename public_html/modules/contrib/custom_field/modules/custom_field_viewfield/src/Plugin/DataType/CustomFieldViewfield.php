<?php

declare(strict_types=1);

namespace Drupal\custom_field_viewfield\Plugin\DataType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\custom_field\Plugin\DataType\CustomFieldDataTypeBase;
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
    // Writing the extra properties here only covers the case where this
    // property object is the one being set. CustomItem::setValue() puts the
    // same keys into the item's values array so they also survive core's
    // property re-sync. Both paths call the type plugin, so they cannot drift.
    $field_type = $this->getDataDefinition()->getSetting('field_type');
    // Resolved through the plugin definition rather than naming ViewfieldType
    // directly, so any subfield type reusing this data type gets its own
    // extraction. The plugin manager already caches definitions per request.
    $definition = $field_type
      ? \Drupal::service('plugin.manager.custom_field_type')->getDefinition($field_type, FALSE)
      : NULL;
    $class = $definition['class'] ?? NULL;
    if ($class !== NULL && class_exists($class)) {
      $extra = $class::extractExtraPropertyValues(['name' => $this->getName()], $value);
      foreach ($extra as $property => $property_value) {
        $this->getParent()->set($property, $property_value);
      }
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
