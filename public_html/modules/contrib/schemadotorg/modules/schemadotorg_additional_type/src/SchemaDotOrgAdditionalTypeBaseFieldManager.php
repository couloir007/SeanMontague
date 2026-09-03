<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;

/**
 * Manages derived additional type base fields.
 */
class SchemaDotOrgAdditionalTypeBaseFieldManager implements SchemaDotOrgAdditionalTypeBaseFieldManagerInterface {
  use StringTranslationTrait;
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs an additional type base field manager.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Plugin\CachedDiscoveryClearerInterface $pluginCacheClearer
   *   The plugin cache clearer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager
   *   The entity definition update manager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository
   *   The entity last installed schema repository.
   */
  public function __construct(
    protected Connection $database,
    protected ConfigFactoryInterface $configFactory,
    protected CachedDiscoveryClearerInterface $pluginCacheClearer,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
    protected EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository,
  ) {}

  /**
   * Gets enabled base field entity type ids.
   *
   * @return array
   *   The enabled base field entity type ids.
   */
  protected function getEnabledEntityTypeIds(): array {
    return $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('create_base_fields') ?? [];
  }

  /**
   * Determines if base field support is enabled for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID to check.
   *
   * @return bool
   *   TRUE if base fields are enabled for this entity type, FALSE otherwise.
   */
  protected function isBaseFieldEnabled(string $entity_type_id): bool {
    return in_array($entity_type_id, $this->getEnabledEntityTypeIds());
  }

  /**
   * Determines if base field support is enabled for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID to check.
   *
   * @return bool
   *   TRUE if base fields are supported for this entity type, FALSE otherwise.
   */
  protected function isBaseFieldSupported(string $entity_type_id): bool {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    return ($entity_type instanceof ContentEntityTypeInterface)
      && ($entity_type->getDataTable() || $entity_type->getBaseTable());
  }

  /**
   * Get additional type field names for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   The additional type field names with their bundles.
   */
  protected function getAdditionalTypeFieldNames(string $entity_type_id): array {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
    $mappings = $this->getMappingStorage()
      ->loadByProperties(['target_entity_type_id' => $entity_type_id]);

    $field_names = [];
    foreach ($mappings as $mapping) {
      $field_name = $mapping->getSchemaPropertyFieldName('additionalType');
      if ($field_name) {
        $field_names += [$field_name => []];
        $bundle = $mapping->getTargetBundle();
        $field_names[$field_name][$bundle] = $bundle;
      }
    }
    return $field_names;
  }

  /* ************************************************************************ */
  // Base field definitions.
  /* ************************************************************************ */

  /**
   * Get base field definitions.
   *
   * @return array
   *   Base field definitions.
   */
  protected function getBaseFieldDefinitions(): array {
    $fields = [];

    $fields[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE] = BaseFieldDefinition::create('list_string')
      ->setName(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE)
      ->setLabel($this->t('Schema.org full type'))
      ->setDescription($this->t('Stores the resolved bundle and additional type for the entity.'))
      ->setRevisionable(TRUE)
      // 288 = Additional type (255) + separator (1) + bundle (32).
      ->setSetting('max_length', 288)
      ->setSetting('allowed_values', [])
      ->setSetting('allowed_values_function', '\Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManager::allowedValuesCallback');

    $fields[SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE] = BaseFieldDefinition::create('list_string')
      ->setName(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE)
      ->setLabel($this->t('Schema.org additional type'))
      ->setDescription($this->t('Stores the resolved Schema.org additional type for the entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('allowed_values', [])
      ->setSetting('allowed_values_function', '\Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManager::allowedValuesCallback');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function baseFieldInfo(EntityTypeInterface $entity_type): array {
    return ($this->isBaseFieldEnabled($entity_type->id()) && $this->isBaseFieldSupported($entity_type->id()))
      ? $this->getBaseFieldDefinitions()
      : [];
  }

  /**
   * {@inheritdoc}
   */
  public function syncBaseFields(array $current_types, array $target_types): void {
    $current_types = array_values($current_types);
    $target_types = array_values($target_types);

    $types_to_delete = array_diff($current_types, $target_types);
    foreach ($types_to_delete as $entity_type_id) {
      if ($this->isBaseFieldSupported($entity_type_id)) {
        $this->deleteBaseFields($entity_type_id);
      }
    }

    $types_to_create = array_diff($target_types, $current_types);
    foreach ($types_to_create as $entity_type_id) {
      if ($this->isBaseFieldSupported($entity_type_id)) {
        $this->createBaseFields($entity_type_id);
      }
    }

    if ($types_to_delete || $types_to_create) {
      $this->pluginCacheClearer->clearCachedDefinitions();
    }
  }

  /**
   * Creates the additional type base fields.
   */
  protected function createBaseFields(string $entity_type_id): void {
    $this->installBaseFields($entity_type_id);
    $this->populateBaseFields($entity_type_id);
  }

  /**
   * Deletes the additional type base fields.
   */
  protected function deleteBaseFields(string $entity_type_id): void {
    $this->uninstallBaseFields($entity_type_id);
  }

  /**
   * Installs derived base fields when missing.
   */
  protected function installBaseFields(string $entity_type_id): void {
    foreach ($this->getBaseFieldDefinitions() as $field_name => $field_definition) {
      if (!$this->entityDefinitionUpdateManager->getFieldStorageDefinition($field_name, $entity_type_id)) {
        $this->entityDefinitionUpdateManager->installFieldStorageDefinition(
          $field_name,
          $entity_type_id,
          'schemadotorg_additional_type',
          $field_definition
        );
      }
    }
  }

  /**
   * Uninstalls derived base fields when present.
   */
  protected function uninstallBaseFields(string $entity_type_id): void {
    foreach (array_keys($this->getBaseFieldDefinitions()) as $field_name) {
      $field_storage_definition = $this->entityDefinitionUpdateManager
        ->getFieldStorageDefinition($field_name, $entity_type_id);
      if ($field_storage_definition) {
        $this->entityDefinitionUpdateManager
          ->uninstallFieldStorageDefinition($field_storage_definition);
      }
    }
  }

  /**
   * Populates derived base fields for supported mappings.
   *
   * @param string $entity_type_id
   *   The entity type id.
   */
  protected function populateBaseFields(string $entity_type_id): void {
    $this->clearBaseFieldValues($entity_type_id);

    // Load the entity type definition.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);

    // Get the field data and revision tables and keys.
    $tables = [];
    $data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    $tables[$entity_type_id] = $data_table;
    if ($entity_type->isRevisionable()) {
      $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
      $tables[$entity_type_id . '_revision'] = $revision_table;
    }
    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');
    $langcode_key = $entity_type->getKey('langcode');

    $field_names = $this->getAdditionalTypeFieldNames($entity_type_id);
    foreach (array_keys($field_names) as $field_name) {
      foreach ($tables as $table_field_prefix => $table_destination) {
        $result = $this->database
          ->select($table_field_prefix . '__' . $field_name, 'f')
          ->fields('f', ['bundle', 'entity_id', 'revision_id', 'langcode', $field_name . '_value'])
          ->condition('delta', 0)
          ->execute();

        while ($row = $result->fetchAssoc()) {
          $additional_type = $row[$field_name . '_value'];
          $full_type = $row['bundle'] . '--' . $additional_type;
          $this->database->update($table_destination)
            ->fields([
              static::ADDITIONAL_TYPE => $additional_type,
              static::FULL_TYPE => $full_type,
            ])
            ->condition($id_key, $row['entity_id'])
            ->condition($revision_key, $row['revision_id'])
            ->condition($langcode_key, $row['langcode'])
            ->execute();
        }
      }
    }
  }

  /**
   * Clears derived base field values.
   */
  protected function clearBaseFieldValues(string $entity_type_id): void {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);

    $data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    $this->database->update($data_table)
      ->fields([
        SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE => NULL,
        SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE => NULL,
      ])
      ->execute();

    if ($entity_type->isRevisionable()) {
      $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
      $this->database->update($revision_table)
        ->fields([
          SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE => NULL,
          SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE => NULL,
        ])
        ->execute();
    }
  }

  /* ************************************************************************ */
  // Sync entity base field values.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function presaveEntity(ContentEntityInterface $entity): void {
    if (!$this->isBaseFieldEnabled($entity->getEntityTypeId())
      || !$this->isBaseFieldSupported($entity->getEntityTypeId())) {
      return;
    }

    $full_type = $entity->bundle();
    $additional_type = NULL;

    // Get the additional type and append it to the full type.
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if ($mapping) {
      $additional_type_field_name = $mapping->getSchemaPropertyFieldName('additionalType');
      if ($additional_type_field_name && $entity->hasField($additional_type_field_name)) {
        $additional_type = $entity->get($additional_type_field_name)->value;
        if ($additional_type) {
          $full_type .= '--' . $additional_type;
        }
      }
    }

    $entity->set(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE, $full_type);
    $entity->set(SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE, $additional_type);
  }

  /* ************************************************************************ */
  // Allowed values.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function getAllowedValues(string $entity_type_id, string $field_name): array {
    $allowed_values = [];
    $field_names = $this->getAdditionalTypeFieldNames($entity_type_id);
    if ($field_name === static::ADDITIONAL_TYPE) {
      foreach (array_keys($field_names) as $additional_type_field_name) {
        /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
        $field_storage_config = $this->entityTypeManager
          ->getStorage('field_storage_config')
          ->load("$entity_type_id.$additional_type_field_name");
        $allowed_values += options_allowed_values($field_storage_config);
      }
    }
    else {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $bundle_entity_type = $entity_type->getBundleEntityType();
      $bundle_entities = $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();

      // Add all bundle entities to the allowed values.
      foreach ($bundle_entities as $bundle_entity) {
        $allowed_values[$bundle_entity->id()] = $bundle_entity->label();
      }

      foreach ($field_names as $additional_type_field_name => $bundles) {
        /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
        $field_storage_config = $this->entityTypeManager
          ->getStorage('field_storage_config')
          ->load("$entity_type_id.$additional_type_field_name");
        $field_allowed_values = options_allowed_values($field_storage_config);
        foreach ($bundles as $bundle) {
          $bundle_entity = $this->entityTypeManager->getStorage($bundle_entity_type)->load($bundle);
          $bundle_label = $bundle_entity ? $bundle_entity->label() : $bundle;
          foreach ($field_allowed_values as $value => $text) {
            $allowed_values[$bundle . '--' . $value] = $bundle_label . ': ' . $text;
          }
        }
      }
    }

    ksort($allowed_values);

    return $allowed_values;
  }

  /**
   * Gets allowed values for the derived additional type base field.
   */
  public static function allowedValuesCallback(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL, bool &$cacheable = TRUE): array {
    /** @var \Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManagerInterface $base_field_manager */
    $base_field_manager = \Drupal::service('schemadotorg_additional_type.base_field_manager');
    return $base_field_manager->getAllowedValues($definition->getTargetEntityTypeId(), $definition->getName());
  }

  /* ************************************************************************ */
  // View integration.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function viewsDataAlter(array &$data): void {
    $field_names = [
      SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::FULL_TYPE,
      SchemaDotOrgAdditionalTypeBaseFieldManagerInterface::ADDITIONAL_TYPE,
    ];
    foreach ($this->getEnabledEntityTypeIds() as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
      if (!$data_table || !isset($data[$data_table])) {
        continue;
      }

      foreach ($field_names as $field_name) {
        if (isset($data[$data_table][$field_name]['filter'])) {
          $data[$data_table][$field_name]['filter']['id'] = 'in_operator';
          $data[$data_table][$field_name]['filter']['options callback'] = '\Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManager::viewsFilterOptionsCallback';
          $data[$data_table][$field_name]['filter']['options arguments'] = [$entity_type_id, $field_name];
        }
      }
    }
  }

  /**
   * Gets allowed values for a view filter entity type's base field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The base field name.
   *
   * @return array
   *   The allowed values for a view filter entity type's base field.
   */
  public static function viewsFilterOptionsCallback(string $entity_type_id, string $field_name): array {
    /** @var \Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeBaseFieldManagerInterface $base_field_manager */
    $base_field_manager = \Drupal::service('schemadotorg_additional_type.base_field_manager');
    return $base_field_manager->getAllowedValues($entity_type_id, $field_name);
  }

}
