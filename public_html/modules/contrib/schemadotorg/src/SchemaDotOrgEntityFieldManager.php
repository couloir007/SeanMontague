<?php

declare(strict_types=1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;

/**
 * Schema.org entity field manager.
 *
 * The Schema.org entity field manager manages the creation and configuration
 * of fields based on Schema.org properties.
 *
 * This service is primarily using by the SchemaDotOrgMappingManager.
 *
 * Features include:
 * - Determining the default field type for a Schema.org property.
 * - Determining the default allowed values for a Schema.org property.
 *
 * @see \Drupal\schemadotorg\SchemaDotOrgMappingManager
 */
class SchemaDotOrgEntityFieldManager implements SchemaDotOrgEntityFieldManagerInterface {
  use StringTranslationTrait;
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Cache for property default fields.
   */
  protected array $propertyDefaultFieldsCache = [];

  /**
   * Cache for field types as options.
   */
  protected array $fieldTypesAsOptionsCache;

  /**
   * Constructs a SchemaDotOrgEntityFieldManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $fieldTypePluginManager
   *   The field type plugin manager.
   * @param \Drupal\Core\Field\FieldTypeCategoryManagerInterface $fieldTypeCategoryManager
   *   The field type category plugin manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected FieldTypePluginManagerInterface $fieldTypePluginManager,
    protected FieldTypeCategoryManagerInterface $fieldTypeCategoryManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function fieldExists(string $entity_type_id, string $bundle, string $field_name): bool {
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      return FALSE;
    }

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    return isset($field_definitions[$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldStorageExists(string $entity_type_id, string $field_name): bool {
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      return FALSE;
    }

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    return isset($field_storage_definitions[$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getField(string $entity_type_id, string $field_name): ?EntityInterface {
    $field_ids = $this->entityTypeManager->getStorage('field_config')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_type', $entity_type_id)
      ->condition('field_name', $field_name)
      ->execute();
    if ($field_ids) {
      return $this->entityTypeManager->getStorage('field_config')
        ->load(reset($field_ids));
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefaultField(string $entity_type_id, string $schema_type, string $schema_property): array {
    $cid = "$entity_type_id.$schema_type.$schema_property";
    if (isset($this->propertyDefaultFieldsCache[$cid])) {
      return $this->propertyDefaultFieldsCache[$cid];
    }

    $default_field = [];

    // Get custom field default settings.
    $default_fields = $this->configFactory
      ->get('schemadotorg.settings')
      ->get('schema_properties.default_fields');
    $parts = [
      'entity_type_id' => $entity_type_id,
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
    ];
    $settings = $this->schemaTypeManager->getSetting($default_fields, $parts, ['multiple' => TRUE]) ?? [];
    foreach ($settings as $setting) {
      $default_field += $setting;
    }

    // Get property field default settings.
    $property_definition = $this->schemaTypeManager->getProperty($schema_property);
    if (!$property_definition) {
      $this->propertyDefaultFieldsCache[$cid] = $default_field;
      return $this->propertyDefaultFieldsCache[$cid];
    }

    $default_field += [
      'name' => $property_definition['drupal_name'],
      'label' => $property_definition['drupal_label'],
      'description' => $property_definition['drupal_description'],
      'unlimited' => $this->unlimitedProperties[$schema_property] ?? FALSE,
      'required' => FALSE,
      'copy' => FALSE,
    ];

    // Allow modules to alter the default field via a hook.
    $this->moduleHandler->invokeAllWith(
      'schemadotorg_property_field_prepare',
      function (callable $hook) use (&$default_field, $entity_type_id, $schema_type, $schema_property): void {
        $hook($default_field, $entity_type_id, $schema_type, $schema_property);
      }
    );

    $this->propertyDefaultFieldsCache[$cid] = $default_field;
    return $this->propertyDefaultFieldsCache[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyFieldTypeOptions(string $entity_type_id, string $schema_type, string $schema_property): array {
    $recommended_field_types = $this->getSchemaPropertyFieldTypes($entity_type_id, $schema_type, $schema_property);

    // Populate recommended options from field types.
    $field_type_options = $this->getFieldTypesAsOptions();
    $recommended_options = [];
    foreach ($field_type_options as $category => &$category_options) {
      foreach ($category_options as $field_name => $field_label) {
        if (isset($recommended_field_types[$field_name])) {
          $recommended_field_types[$field_name] = $field_label;
          $recommended_options[$field_name] = $field_label;
          unset($category_options[$field_name]);
        }
      }

      // Remove empty field type category.
      if (empty($category_options)) {
        unset($field_type_options[$category]);
      }
    }

    // Prepend sorted recommended field types to field type options.
    if ($recommended_options) {
      $recommended_category = (string) $this->t('Recommended');

      // @see https://stackoverflow.com/questions/348410/sort-an-array-by-keys-based-on-another-array#answer-9098675
      $recommended_options = array_intersect_key($recommended_field_types, $recommended_options);

      $field_type_options = [$recommended_category => $recommended_options]
        + $field_type_options;
    }

    return $field_type_options;
  }

  /**
   * Gets field types as options.
   *
   * @return array
   *   Field types as options.
   */
  protected function getFieldTypesAsOptions(): array {
    if (isset($this->fieldTypesAsOptionsCache)) {
      return $this->fieldTypesAsOptionsCache;
    }

    // Manually create grouped definitions as options to ensure categorization
    // works as expected.
    // @see \Drupal\Core\Field\FieldTypePluginManager::getGroupedDefinitions
    $field_definitions = $this->fieldTypePluginManager->getUiDefinitions();
    $category_info = $this->fieldTypeCategoryManager->getDefinitions();
    $options = [];
    foreach ($field_definitions as $name => $field_definition) {
      // We are adjusting the field type categories because in the
      // field UI module it is a two-step process with icons
      // for selecting a field type and in the Schema.org Blueprints UI
      // we are using a select menu with grouping.
      $category = match($name) {
        // Reference.
        'field_ui:entity_reference:media',
        'entity_reference_override',
        'field_ui:entity_reference_revisions:paragraph'
        => 'reference',
        // Date and time.
        'duration',
        'time',
        'time_range'
        => 'date_time',
        // Default.
        default => $field_definition['category'],
      };

      // Support categories as translatable string and the newer category info.
      $optgroup = ($category instanceof TranslatableMarkup)
        ? (string) $category
        : (string) $category_info[$category]['label'];

      $options[$optgroup][$name] = $field_definition['label'];
    }

    // Sort options.
    asort($options);
    foreach ($options as &$items) {
      asort($items);
    }

    // Move the 'General' category to the top.
    $general_category = (string) $this->t('General');
    if (!empty($options[$general_category])) {
      $options = [$general_category => $options[$general_category]] + $options;
    }

    $this->fieldTypesAsOptionsCache = $options;
    return $this->fieldTypesAsOptionsCache;
  }

  /**
   * Gets the current entity's fields as options.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   The current entity's fields as options.
   */
  protected function getFieldDefinitionsAsOptions(string $entity_type_id, string $bundle): array {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    $field_definitions = array_diff_key(
      $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle),
      $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id)
    );

    $options = [];
    foreach ($field_definitions as $field_definition) {
      $options[$field_definition->getName()] = $this->t('@field [@type]', [
        '@type' => $field_types[$field_definition->getType()]['label'],
        '@field' => $field_definition->getLabel(),
      ]);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldOptions(string $entity_type_id, string $bundle): array {
    $options = [];
    $options[static::ADD_FIELD] = $this->t('Add a new field…');

    $field_definition_options = $this->getFieldDefinitionsAsOptions($entity_type_id, $bundle);
    if ($field_definition_options) {
      $options[(string) $this->t('Fields')] = $field_definition_options;
    }

    $base_field_definition_options = $this->getBaseFieldDefinitionsOptions($entity_type_id, $bundle);
    if ($base_field_definition_options) {
      $options[(string) $this->t('Base fields')] = $base_field_definition_options;
    }

    $existing_field_storage_options = $this->getExistingFieldStorageOptions($entity_type_id, $bundle);
    if ($existing_field_storage_options) {
      $options[(string) $this->t('Existing fields')] = $existing_field_storage_options;
    }
    return $options;
  }

  /**
   * Gets base fields as options.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   Base fields as options.
   */
  protected function getBaseFieldDefinitionsOptions(string $entity_type_id, string $bundle): array {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $options = [];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface|null $mapping_type */
    $mapping_type = $this->getMappingTypeStorage()->load($entity_type_id);
    $base_field_names = $mapping_type->getBaseFieldNames();
    if ($base_field_names) {
      foreach ($base_field_names as $field_name) {
        if (isset($field_definitions[$field_name])) {
          $field_definition = $field_definitions[$field_name];
          $options[$field_definition->getName()] = $this->t('@field [@type]', [
            '@type' => $field_types[$field_definition->getType()]['label'],
            '@field' => $field_name,
          ]);
        }
      }
    }
    else {
      foreach ($field_definitions as $field_name => $field_definition) {
        $options[$field_definition->getName()] = $this->t('@field [@type]', [
          '@type' => $field_types[$field_definition->getType()]['label'],
          '@field' => $field_name,
        ]);
      }
    }

    return $options;
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of existing field storages keyed by name.
   *
   * @see \Drupal\field_ui\Form\FieldStorageAddForm::getExistingFieldStorageOptions
   */
  protected function getExistingFieldStorageOptions(string $entity_type_id, string $bundle): array {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    // Load the field_storages and build the list of options.
    $options = [];
    foreach ($this->entityFieldManager->getFieldStorageDefinitions($entity_type_id) as $field_name => $field_storage) {
      // Do not show:
      // - non-configurable field storages,
      // - locked field storages,
      // - field storages that should not be added via user interface,
      // - field storages that already have a field in the bundle.
      $field_type = $field_storage->getType();
      if ($field_storage instanceof FieldStorageConfigInterface
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])
        && !in_array($bundle, $field_storage->getBundles(), TRUE)) {
        $options[$field_name] = $this->t('@field [@type]', [
          '@type' => $field_types[$field_type]['label'],
          '@field' => $field_name,
        ]);
      }
    }
    asort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyFieldTypes(string $entity_type_id, string $schema_type, string $schema_property): array {
    $range_includes = $this->getMappingStorage()->getSchemaPropertyRangeIncludes($schema_type, $schema_property);

    // Remove generic Schema.org types from range includes.
    $specific_range_includes = $range_includes;
    unset(
      $specific_range_includes['Thing'],
      $specific_range_includes['CreativeWork'],
      $specific_range_includes['Intangible'],
      $specific_range_includes['StructuredValue']
    );

    // Remove DefinedTerm & CategoryCode which are used by taxonomy
    // from range includes.
    unset(
      $specific_range_includes['DefinedTerm'],
      $specific_range_includes['CategoryCode'],
    );

    // Set default entity reference type and field type.
    $entity_reference_entity_type = $this->getDefaultEntityReferenceEntityType($specific_range_includes);
    $entity_reference_field_type = $this->getDefaultEntityReferenceFieldType($entity_reference_entity_type);

    $field_types = [];

    // Set Schema.org property specific field types.
    $default_field = $this->getPropertyDefaultField($entity_type_id, $schema_type, $schema_property);
    $default_field_type = $default_field['type'] ?? NULL;
    if ($this->fieldTypeExists($default_field_type)) {
      $field_types[$default_field_type] = $default_field_type;
    }

    // Check specific Schema.org type entity reference target bundles
    // (a.k.a. range_includes) exist.
    $entity_reference_target_bundles = $this->getMappingStorage()->getRangeIncludesTargetBundles($entity_reference_entity_type, $specific_range_includes);
    if ($entity_reference_target_bundles) {
      $field_types[$entity_reference_field_type] = $entity_reference_field_type;
    }

    // Check Schema.org property and type specific field type from settings.
    if (empty($field_types)) {
      // Append property includes field types.
      $schema_property_field_types = $this->getSchemaPropertyDefaultFieldTypes();
      $field_types += $schema_property_field_types["$schema_type--$schema_property"]
        ?? $schema_property_field_types[$schema_property]
        ?? [];

      // Append range includes field types.
      $schema_type_field_types = $this->getSchemaTypeDefaultFieldTypes();
      foreach ($schema_type_field_types as $type_name => $type_mapping) {
        if (isset($range_includes[$type_name])) {
          $field_types += $type_mapping;
        }
      }
    }

    // Check generic Schema.org type entity reference target bundles
    // (a.k.a. range_includes) exist.
    if ($range_includes !== $specific_range_includes) {
      $generic_range_includes = array_diff_key($range_includes, $specific_range_includes);
      $entity_reference_target_bundles = $this->getMappingStorage()->getRangeIncludesTargetBundles($entity_reference_entity_type, $generic_range_includes);
      if ($entity_reference_target_bundles) {
        $field_types[$entity_reference_field_type] = $entity_reference_field_type;
      }
    }

    // Set default field types to string and entity reference.
    if (empty($field_types)) {
      $field_types += [
        'string' => 'string',
        $entity_reference_field_type => $entity_reference_field_type,
      ];
    }

    // Allow modules to alter property field types.
    // Allow modules to alter the default field via a hook.
    $this->moduleHandler->invokeAllWith(
      'schemadotorg_property_field_type_alter',
      function (callable $hook) use (&$field_types, $entity_type_id, $schema_type, $schema_property): void {
        $hook($field_types, $entity_type_id, $schema_type, $schema_property);
      }
    );

    return $field_types;
  }

  /**
   * Get default Schema.org type field types.
   *
   * @return array
   *   Schema.org type field types.
   */
  protected function getSchemaTypeDefaultFieldTypes(): array {
    $schema_field_types = &drupal_static(__METHOD__);
    if (!isset($schema_field_types)) {
      $schema_field_types = $this->configFactory
        ->get('schemadotorg.settings')
        ->get('schema_types.default_field_types');
      foreach ($schema_field_types as $id => $field_types) {
        $schema_field_types[$id] = array_combine($field_types, $field_types);
        foreach ($schema_field_types[$id] as $field_type) {
          if (!$this->fieldTypeExists($field_type)) {
            unset($schema_field_types[$id][$field_type]);
          }
        }
      }
    }
    return $schema_field_types;
  }

  /**
   * Get default Schema.org property field types.
   *
   * @return array
   *   Schema.org property field types.
   */
  protected function getSchemaPropertyDefaultFieldTypes(): array {
    $schema_field_types = &drupal_static(__METHOD__);
    if (!isset($schema_field_types)) {
      $schema_field_types = $this->configFactory
        ->get('schemadotorg.settings')
        ->get('schema_properties.default_field_types');
      foreach ($schema_field_types as $id => $field_types) {
        $schema_field_types[$id] = array_combine($field_types, $field_types);
        foreach ($schema_field_types[$id] as $field_type) {
          if (!$this->fieldTypeExists($field_type)) {
            unset($schema_field_types[$id][$field_type]);
          }
        }
      }
    }
    return $schema_field_types;
  }

  /**
   * Determine if a field type exists.
   *
   * @param string|null $type
   *   The field type.
   *
   * @return bool
   *   TRUE if the field type exists.
   */
  protected function fieldTypeExists(?string $type): bool {
    $field_type_definitions = &drupal_static(__METHOD__);
    if (!isset($field_type_definitions)) {
      $field_type_definitions = $this->fieldTypePluginManager->getUiDefinitions();
    }
    return isset($field_type_definitions[$type]);
  }

  /**
   * Get default entity reference field type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   Default entity reference field type.
   */
  protected function getDefaultEntityReferenceFieldType(string $entity_type_id): string {
    return ($entity_type_id === 'paragraph')
      ? 'field_ui:entity_reference_revisions:paragraph'
      : "field_ui:entity_reference:$entity_type_id";
  }

  /**
   * Gets the entity reference entity type based on an array Schema.org types.
   *
   * @param array $types
   *   Schema.org types, extracted from a property's range includes.
   *
   * @return string
   *   The entity reference entity type.
   */
  protected function getDefaultEntityReferenceEntityType(array $types): string {
    // Remove 'Thing' from $types because it is too generic.
    $types = array_combine($types, $types);
    unset($types['Thing']);

    // Loop through the types to respect the ordering and prioritization.
    foreach ($types as $type) {
      $sub_types = $this->schemaTypeManager->getAllSubTypes([$type]);
      if (empty($sub_types)) {
        continue;
      }

      $entity_ids = $this->getMappingStorage()->getQuery()
        ->accessCheck(FALSE)
        ->condition('schema_type', $sub_types, 'IN')
        ->execute();
      if (empty($entity_ids)) {
        continue;
      }

      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $schemadotorg_mappings */
      $schemadotorg_mappings = $this->getMappingStorage()->loadMultiple($entity_ids);

      // Define the default order for found entity types.
      $entity_types = [
        'media' => NULL,
        'paragraph' => NULL,
        'block_content' => NULL,
        'node' => NULL,
        'user' => NULL,
      ];
      foreach ($schemadotorg_mappings as $schemadotorg_mapping) {
        $entity_types[$schemadotorg_mapping->getTargetEntityTypeId()] = $schemadotorg_mapping->getTargetEntityTypeId();
      }

      // Filter the entity types so that only found entity types are included.
      $entity_types = array_filter($entity_types);

      // Get first entity type.
      return reset($entity_types);
    }

    return 'node';
  }

}
