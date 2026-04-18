<?php

namespace Drupal\content_model_documentation;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Helper service for our module.
 */
class FieldsReportManager implements FieldsReportManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The list of all content entity types.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface[]
   */
  protected $contentEntityTypes;

  /**
   * The list of all active field definitions, keyed by entity type.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentEntityTypes(?array $entity_type_ids = NULL) {
    if (!isset($this->contentEntityTypes)) {
      $this->contentEntityTypes = array_filter(
        $this->entityTypeManager->getDefinitions(),
        function (EntityTypeInterface $entity_type) {
          return $entity_type instanceof ContentEntityTypeInterface;
        }
      );
    }

    if (!empty($entity_type_ids)) {
      return array_filter(
        $this->contentEntityTypes,
        function (EntityTypeInterface $entity_type) use ($entity_type_ids) {
          return in_array($entity_type->id(), $entity_type_ids);
        }
      );
    }

    return $this->contentEntityTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions(?array $entity_type_ids = NULL) {
    if (!isset($this->fieldDefinitions)) {
      $this->fieldDefinitions = [];
      foreach (array_keys($this->getContentEntityTypes()) as $entity_type_id) {
        $this->fieldDefinitions[$entity_type_id] = $this->entityFieldManager->getActiveFieldStorageDefinitions($entity_type_id);
      }
    }

    if (!empty($entity_type_ids)) {
      return array_filter(
        $this->fieldDefinitions,
        function ($entity_type_id) use ($entity_type_ids) {
          return in_array($entity_type_id, $entity_type_ids);
        },
        ARRAY_FILTER_USE_KEY
      );
    }

    return $this->fieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition(string $entity_type_id, string $name) {
    $field_definitions = $this->getFieldDefinitions([$entity_type_id]);
    return $field_definitions[$entity_type_id][$name] ?? NULL;
  }

  /**
   * Get the field count for all bundles.
   *
   * @return array
   *   The field count and bundle id.
   */
  public function getFieldsCountBundleWise() {
    $entity_types = $this->getContentEntityTypes();
    foreach ($entity_types as $entity_type) {
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type->id());
      foreach ($bundle_info as $bundle_id => $bundle) {
        $bundles[$entity_type->id()][] = [
          "id" => $bundle_id,
          "field_count" => $this->getFieldCountForBundle($entity_type->id(), $bundle_id),
        ];
      }
    }

    return $bundles;
  }

  /**
   * Get the field count for a specific bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return int
   *   The field count.
   */
  protected function getFieldCountForBundle($entity_type_id, $bundle_id) {
    // Retrieve both base and bundle fields.
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);

    // Filter out base fields.
    $custom_fields = array_filter($field_definitions, function ($field_definition) use ($base_field_definitions) {
      return !isset($base_field_definitions[$field_definition->getName()]);
    });

    return count($custom_fields);
  }

}
