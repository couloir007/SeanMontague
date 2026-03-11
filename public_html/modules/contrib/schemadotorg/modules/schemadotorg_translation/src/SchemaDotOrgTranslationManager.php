<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_translation;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;

/**
 * Schema.org translate manager.
 */
class SchemaDotOrgTranslationManager implements SchemaDotOrgTranslationManagerInterface {
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgTranslationManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager
   *   The entity field manager.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $contentTranslationManager
   *   The content translation manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $fieldManager,
    protected ContentTranslationManagerInterface $contentTranslationManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applyTranslations(): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
    $mappings = $this->getMappingStorage()->loadMultiple();
    foreach ($mappings as $mapping) {
      $target_bundle_entity = $mapping->getTargetEntityBundleEntity();
      $target_bundle_entity->schemaDotOrgType = $mapping->getSchemaType();
      $this->entityInsert($target_bundle_entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityInsert(EntityInterface $entity): void {
    if (!isset($entity->schemaDotOrgType)
      || empty($entity->getEntityType()->getBundleOf())) {
      return;
    }

    $entity_type_id = $entity->getEntityType()->getBundleOf();
    $bundle = $entity->id();
    $schema_type = $entity->schemaDotOrgType;

    // Check that Schema.org mapping entity type, bundle,
    // and Schema.org type is translated.
    if (!$this->isEntityTranslated($entity_type_id, $bundle, $schema_type)) {
      return;
    }

    // Enable translation for an entity type.
    $this->contentTranslationManager->setEnabled($entity_type_id, $bundle, TRUE);

    // Enable translation for all existing fields and resave them.
    $field_definitions = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($field_definitions as $field_definition) {
      /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
      $field_config = $field_definition->getConfig($bundle);
      if (!$this->supportsFieldTranslations($field_config)) {
        continue;
      }

      // Track if the field config's translatable needs to be updated, and
      // resave th3 field config.
      $translatable = $field_config->get('translatable');
      $this->setFieldConfigTranslatable($field_config);
      if ($translatable !== $field_config->get('translatable')) {
        $field_config->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldConfigPresave(FieldConfigInterface $field_config): void {
    // Check that field is associated with Schema.org type mapping.
    // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::addFieldToEntity
    $target_entity_type_id = $field_config->getTargetEntityTypeId();
    $target_bundle = $field_config->getTargetBundle();

    if (empty($field_config->schemaDotOrgType)
      && !$this->loadMapping($target_entity_type_id, $target_bundle)) {
      return;
    }

    if ($this->supportsFieldTranslations($field_config)) {
      $this->setFieldConfigTranslatable($field_config);
    }
  }

  /**
   * Set the translatability settings for a field configuration.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field configuration entity.
   */
  protected function setFieldConfigTranslatable(FieldConfigInterface $field_config): void {
    // Check that the field is translated.
    if (!$this->isFieldTranslated($field_config)) {
      $field_config->setTranslatable(FALSE);
      return;
    }

    // Set translatable.
    $field_config->setTranslatable(TRUE);

    // Set third party settings.
    $field_type = $field_config->getType();
    switch ($field_type) {
      case 'image':
        $column_settings = [
          'alt' => 'alt',
          'title' => 'title',
          'file' => 0,
        ];
        $field_config->setThirdPartySetting('content_translation', 'translation_sync', $column_settings);
        break;
    }
  }

  /**
   * Determines if the provided entity type and bundle are translatable.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return bool
   *   TRUE if the entity type and bundle are translatable, FALSE otherwise.
   */
  protected function isEntityTranslated(string $entity_type_id, string $bundle, string $schema_type): bool {
    $config = $this->configFactory->get('schemadotorg_translation.settings');

    // Check excluded Schema.org type.
    $excluded_schema_types = $config->get('excluded_schema_types');
    $parts = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'schema_type' => $schema_type,
    ];
    if ($this->schemaTypeManager->getSetting($excluded_schema_types, $parts)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Determine if a field should be translated.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field.
   *
   * @return bool
   *   TRUE if a field should be translated.
   */
  protected function isFieldTranslated(FieldConfigInterface $field_config): bool {
    $entity_type_id = $field_config->getTargetEntityTypeId();
    $bundle = $field_config->getTargetBundle();

    // Check that the entity has translation enabled.
    if (!$this->contentTranslationManager->isEnabled($entity_type_id, $bundle)) {
      return FALSE;
    }

    $config = $this->configFactory->get('schemadotorg_translation.settings');

    // Check excluded Schema.org properties and fields.
    $excluded_schema_properties = $config->get('excluded_schema_properties');
    $field = $field_config->schemaDotOrgField ?? [];
    $parts = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'schema_type' => $field['schema_type'] ?? NULL,
      'schema_property' => $field['schema_property'] ?? NULL,
      'field_name' => $field_config->getName(),
    ];
    if ($this->schemaTypeManager->getSetting($excluded_schema_properties, $parts)) {
      return FALSE;
    }

    // Check included field names.
    if (in_array($field_config->getName(), $config->get('included_field_names'))) {
      return TRUE;
    }

    // Check included field types.
    if (in_array($field_config->getType(), $config->get('included_field_types'))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Determine if a field supports translation.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field.
   *
   * @return bool
   *   TRUE if a field supports translation.
   *
   * @see _content_translation_form_language_content_settings_form_alter()
   */
  protected function supportsFieldTranslations(FieldConfigInterface $field_config): bool {
    $field_name = $field_config->getName();
    $entity_type_id = $field_config->getTargetEntityTypeId();

    // Computed field always support translations.
    if ($field_config->isComputed()) {
      return TRUE;
    }

    // Make sure the field is associate with a content entity.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type instanceof ContentEntityTypeInterface) {
      return FALSE;
    }

    // Get field storage definition.
    $storage_definitions = $this->fieldManager->getFieldStorageDefinitions($entity_type_id);
    $storage_definition = $storage_definitions[$field_name] ?? NULL;
    if (!$storage_definition) {
      return FALSE;
    }

    // Check whether translatability should be configurable for a field.
    // @see _content_translation_is_field_translatability_configurable
    return $storage_definition->isTranslatable() &&
      $storage_definition->getProvider() != 'content_translation' &&
      !in_array($storage_definition->getName(), [$entity_type->getKey('langcode'), $entity_type->getKey('default_langcode'), 'revision_translation_affected']);
  }

}
