<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_layout_paragraphs;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Schema.org layout paragraphs JSON-LD manager.
 */
class SchemaDotOrgLayoutParagraphsJsonldManager implements SchemaDotOrgLayoutParagraphsJsonldManagerInterface {
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgLayoutParagraphsJsonldManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface|null $schemaJsonLdManager
   *   The Schema.org JSON-LD manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface|null $schemaJsonLdBuilder
   *   The Schema.org JSON-LD builder.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    #[Autowire(service: 'schemadotorg_jsonld.manager')]
    protected ?SchemaDotOrgJsonLdManagerInterface $schemaJsonLdManager = NULL,
    #[Autowire(service: 'schemadotorg_jsonld.builder')]
    protected ?SchemaDotOrgJsonLdBuilderInterface $schemaJsonLdBuilder = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function alter(array &$data, RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): void {
    if (!$this->moduleHandler->moduleExists('schemadotorg_additional_mappings')) {
      return;
    }

    $entity = $this->schemaJsonLdManager->getRouteMatchEntity($route_match);
    if (!$entity || !$entity instanceof ContentEntityInterface) {
      return;
    }

    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if (!$mapping) {
      return;
    }

    // Make sure the main mapping does not support https://schema.org/mainEntity.
    $property_name = SchemaDotOrgLayoutParagraphsManagerInterface::PROPERTY_NAME;
    if (!$mapping->getSchemaPropertyFieldName($property_name)
      || $this->schemaTypeManager->hasProperty($mapping->getSchemaType(), $property_name)) {
      return;
    }

    // Check if main Schema.org type supports https://schema.org/mainEntity.
    $schema_type = $data['schemadotorg_jsonld_entity']['@type'];
    if (!$this->schemaTypeManager->hasProperty($schema_type, $property_name)) {
      return;
    }

    // Get the https://schema.org/mainEntity values.
    $field_name = $mapping->getSchemaPropertyFieldName($property_name);
    $values = $this->schemaJsonLdBuilder->getSchemaPropertyFieldItems($schema_type, $property_name, $entity->get($field_name), $bubbleable_metadata);
    if (!$values) {
      return;
    }

    // Append the https://schema.org/mainEntity values to the
    // main Schema.org type.
    if (isset($data['schemadotorg_jsonld_entity'][$property_name])) {
      // Convert existing values to a list.
      if (!array_is_list($data['schemadotorg_jsonld_entity'][$property_name])) {
        $data['schemadotorg_jsonld_entity'][$property_name] = [$data['schemadotorg_jsonld_entity'][$property_name]];
      }
      $data['schemadotorg_jsonld_entity'][$property_name] = array_merge(
        $data['schemadotorg_jsonld_entity'][$property_name],
        $values);
    }
    else {
      $data['schemadotorg_jsonld_entity'][$property_name] = $values;
    }
  }

}
