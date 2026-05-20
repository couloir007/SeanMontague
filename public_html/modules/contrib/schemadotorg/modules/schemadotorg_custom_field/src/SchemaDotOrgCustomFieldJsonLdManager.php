<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_custom_field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Schema.org Custom Field JSON-LD manager.
 */
class SchemaDotOrgCustomFieldJsonLdManager implements SchemaDotOrgCustomFieldJsonLdManagerInterface {

  /**
   * Constructs a SchemaDotOrgCustomFieldJsonLdManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldManagerInterface $schemaCustomFieldManager
   *   The Schema.org Custom Field manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface|null $schemaJsonLdManager
   *   The Schema.org JSON-LD manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface|null $schemaJsonLdBuilder
   *   The Schema.org JSON-LD builder.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgCustomFieldManagerInterface $schemaCustomFieldManager,
    #[Autowire(service: 'schemadotorg_jsonld.manager')]
    protected ?SchemaDotOrgJsonLdManagerInterface $schemaJsonLdManager = NULL,
    #[Autowire(service: 'schemadotorg_jsonld.builder')]
    protected ?SchemaDotOrgJsonLdBuilderInterface $schemaJsonLdBuilder = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void {
    $mapping = $this->schemaCustomFieldManager->getFieldItemSchemaMapping($item);
    if (!$mapping) {
      return;
    }

    $field_name = $item->getFieldDefinition()->getName();
    $mapping_schema_type = $mapping->getSchemaType();
    $schema_property = $mapping->getSchemaPropertyMapping($field_name, TRUE);

    // Check to see if the property has custom field settings.
    $custom_field_settings = $this->schemaCustomFieldManager->getDefaultProperties(
      entity_type_id: $mapping->getTargetEntityTypeId(),
      bundle: $mapping->getTargetBundle(),
      schema_type: $mapping_schema_type,
      schema_property: $schema_property,
    );
    if (!$custom_field_settings) {
      return;
    }

    $settings = $item->getFieldDefinition()->getSettings();
    $values = $item->getValue();

    $custom_field_schema_type = $custom_field_settings['schema_type'];
    $custom_field_schema_role = $custom_field_settings['schema_role'] ?? NULL;

    $data = [
      '@type' => $custom_field_schema_type,
    ];

    // Append custom field properties to the Schema.org data.
    foreach ($values as $item_key => $item_value) {
      $item_property = $this->schemaNames->snakeCaseToCamelCase($item_key);
      $has_value = ($item_value !== '' && $item_value !== NULL);
      if (!$has_value) {
        continue;
      }

      $is_property = $this->schemaTypeManager->isProperty($item_property);
      if (!$is_property) {
        continue;
      }

      // Convert allowed values' value to text.
      $allowed_values = NestedArray::getValue($settings, ['field_settings', $item_key, 'widget_settings', 'settings', 'allowed_values']) ?? [];
      if ($allowed_values) {
        foreach ($allowed_values as $allowed_value) {
          if ($allowed_value['key'] === $item_value) {
            $item_value = $allowed_value['value'];
            break;
          }
        }
      }

      // Prepend the prefix.
      $prefix = $custom_field_settings['schema_properties'][$item_property]['prefix'] ?? NULL;
      if ($prefix) {
        $item_value = $prefix . $item_value;
      }

      // Append the suffix.
      $suffix = $custom_field_settings['schema_properties'][$item_property]['suffix'] ?? NULL;
      if ($suffix) {
        $item_value .= $suffix;
      }

      $data[$item_property] = $this->schemaJsonLdManager->getSchemaPropertyValueDefaultSchemaType(
        $custom_field_schema_type,
        $item_property,
        $item_value
      );
    }

    // Prepend the https://schema.org/Role target id.
    if ($custom_field_schema_role) {

      // Get the target entity.
      $target_entity = NULL;
      if (!empty($values['target_id'])) {
        $target_id = $values['target_id'];
        $settings = $item->getFieldDefinition()->getSetting('target_id');
        $target_type = $settings['widget_settings']['settings']['target_type'] ?? 'node';
        $target_entity = $this->entityTypeManager->getStorage($target_type)->load($target_id);
      }

      if ($target_entity) {
        // Append the target entity JSON-LD.
        $data['@type'] = $custom_field_schema_role;
        $data[$schema_property] = $this->schemaJsonLdBuilder->buildEntity($target_entity, NULL, $bubbleable_metadata);
      }
      else {
        // Split the $data into role JSON-LD data and type JSON-LD data.
        $role_data = ['@type' => $custom_field_schema_role];
        $schema_type_data = ['@type' => $custom_field_schema_type];
        foreach ($data as $item_key => $item_value) {
          if ($this->schemaTypeManager->hasProperty($custom_field_schema_role, $item_key)) {
            $role_data[$item_key] = $item_value;
          }
          elseif ($this->schemaTypeManager->hasProperty($custom_field_schema_type, $item_key)) {
            $schema_type_data[$item_key] = $item_value;
          }
        }
        $data = $role_data + [$schema_property => $schema_type_data];
      }
    }

    $value = $data;
  }

}
