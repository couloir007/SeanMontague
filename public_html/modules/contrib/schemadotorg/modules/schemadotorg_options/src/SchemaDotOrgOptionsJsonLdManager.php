<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_options;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;

/**
 * The Schema.org options JSON-LD manager.
 */
class SchemaDotOrgOptionsJsonLdManager implements SchemaDotOrgOptionsJsonLdManagerInterface {
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgOptionsJsonLdManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    public SchemaDotOrgNamesInterface $schemaNames,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void {
    if (!$item instanceof OptionsProviderInterface || is_array($value)) {
      return;
    }

    $entity = $item->getEntity();

    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    $schema_type = $mapping?->getSchemaType();
    $schema_property = $mapping?->getSchemaPropertyMapping($item->getFieldDefinition()->getName());

    $config = $this->configFactory->get('schemadotorg_options.settings');

    // Convert value to URI.
    $uri = $config->get("jsonld_uris.$schema_type--$schema_property--$value")
      ?? $config->get("jsonld_uris.$schema_property--$value")
      ?? $config->get("jsonld_uris.$value");
    if ($uri) {
      $value = $uri;
    }

    // Convert alias value from an alias to the original value.
    $aliases = $config->get('allowed_value_aliases');
    foreach ($aliases as $alias_enumeration => $alias_value) {
      if ($alias_value === $value
        && (
          !str_contains($alias_enumeration, '--')
          || str_starts_with($alias_enumeration, "$schema_type--$schema_property")
          || str_starts_with($alias_enumeration, "$schema_property--")
        )
      ) {
        $alias_parts = explode('--', $alias_enumeration);
        $alias_value = end($alias_parts);
        $value = $alias_value;
      }
    }

    // Convert snake case enumeration value to camel case enumeration value.
    if ($schema_property) {
      $range_includes = $this->schemaTypeManager->getPropertyRangeIncludes($schema_property);
      if ($range_includes) {
        $use_snake_case = $config->get('use_snake_case');
        foreach ($range_includes as $range_include) {
          if (!$this->schemaTypeManager->isEnumerationType($range_include)) {
            continue;
          }

          $alias_value = ($use_snake_case)
            ? $this->schemaNames->snakeCaseToUpperCamelCase($value)
            : $value;
          if ($this->schemaTypeManager->isEnumerationMemberOf($alias_value, $range_include)) {
            $value = 'https://schema.org/' . $alias_value;
            return;
          }
        }
      }
    }

    // Convert value to (translated) text.
    if ($mapping) {
      $parts = [
        'schema_type' => $schema_type,
        'schema_property' => $schema_property,
      ];
      $jsonld_text = $config->get('jsonld_text');
      if (in_array('Thing', $jsonld_text)
        || $this->schemaTypeManager->getSetting($jsonld_text, $parts)) {
        $allowed_values = options_allowed_values($item->getFieldDefinition()->getFieldStorageDefinition(), $entity);
        $value = $allowed_values[$value] ?? $value;
      }
    }
  }

}
