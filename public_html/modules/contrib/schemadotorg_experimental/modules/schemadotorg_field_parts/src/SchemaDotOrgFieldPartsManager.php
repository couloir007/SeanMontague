<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_field_parts;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org field parts manager.
 */
class SchemaDotOrgFieldPartsManager implements SchemaDotOrgFieldPartsManagerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgFieldPartsManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface $entityTypeBuilder
   *   The Schema.org entity type builder.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface $schemaEntityDisplayBuilder
   *   The Schema.org entity display builder.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgEntityTypeBuilderInterface $entityTypeBuilder,
    protected SchemaDotOrgEntityDisplayBuilderInterface $schemaEntityDisplayBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingPresave(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    $parts = [static::PREFIX, static::SUFFIX];

    // Add new part fields.
    $properties = $mapping->getNewSchemaProperties();
    foreach ($properties as $schema_property) {
      foreach ($parts as $part) {
        if ($this->supportsFieldPart($mapping, $schema_property, $part)
          && !$this->hasFieldPart($mapping, $schema_property, $part)) {
          $this->addFieldPart($mapping, $schema_property, $part);
        }
      }
    }
  }

  /**
   * Determine if a Schema.org mapping property should have a field part.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   * @param string $schema_property
   *   The Schema.org property.
   * @param string $part
   *   The field part.
   *
   * @return bool
   *   TRUE if a Schema.org mapping property should have a field part.
   */
  protected function supportsFieldPart(SchemaDotOrgMappingInterface $mapping, string $schema_property, string $part): bool {
    $config = $this->configFactory->get('schemadotorg_field_parts.settings');
    $part_properties = $config->get($part . '_properties');
    $parts = [
      'entity_type_id' => $mapping->getTargetEntityTypeId(),
      'bundle' => $mapping->getTargetBundle(),
      'schema_type' => $mapping->getSchemaType(),
      'schema_property' => $schema_property,
      'field_name' => $mapping->getSchemaPropertyFieldName($schema_property),
    ];
    return (bool) $this->schemaTypeManager->getSetting($part_properties, $parts);
  }

  /**
   * Determine if a Schema.org mapping property has a field part.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   * @param string $schema_property
   *   The Schema.org property.
   * @param string $part
   *   The field part.
   *
   * @return bool
   *   TRUE if a Schema.org mapping property has a field part.
   */
  protected function hasFieldPart(SchemaDotOrgMappingInterface $mapping, string $schema_property, string $part): bool {
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    $field_name = $mapping->getSchemaPropertyFieldName($schema_property) . '_' . $part;

    /** @var \Drupal\field\FieldConfigStorage $field_config_storage */
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    return (bool) $field_config_storage->load("$entity_type_id.$bundle.$field_name");
  }

  /**
   * A field part to a Schema.org mapping property.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   * @param string $schema_property
   *   The Schema.org property.
   * @param string $part
   *   The field part.
   */
  protected function addFieldPart(SchemaDotOrgMappingInterface $mapping, string $schema_property, string $part): void {
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    $schema_type = $mapping->getSchemaType();
    $field_name = $mapping->getSchemaPropertyFieldName($schema_property);
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $field_definition = $field_definitions[$field_name];
    $field_label = (string) $field_definition->getLabel();

    $t_args = ['@label' => $field_label];
    $label = match ($part) {
      static::PREFIX => $this->t('@label prefix', $t_args),
      static::SUFFIX => $this->t('@label suffix', $t_args),
      default => NULL,
    };

    $t_args = ['@label' => strtolower($field_label)];
    $description = match ($part) {
      static::PREFIX => $this->t('The text which appears before the @label.', $t_args),
      static::SUFFIX => $this->t('The text which appears after the @label.', $t_args),
      default => NULL,
    };

    $field_definition = [
      'label' => $label,
      'description' => $description,
      'field_name' => $field_name . '_' . $part,
      'type' => 'string',
      'schema_type' => $schema_type,
      'schema_property' => $schema_property . ':' . $part,
    ];
    $this->entityTypeBuilder->addFieldToEntity($entity_type_id, $bundle, $field_definition);
  }

}
