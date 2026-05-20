<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org additional type manager interface.
 */
interface SchemaDotOrgAdditionalTypeJsonLdManagerInterface {

  /**
   * Alter the Schema.org JSON-LD data for an entity.
   *
   * Besides, altering an existing Schema.org mapping's JSON-LD data, modules can
   * define custom JSON-LD data for any entity type.
   *
   * @param array $data
   *   The Schema.org JSON-LD data for an entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null $mapping
   *   The entity's Schema.org mapping.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   Object to collect JSON-LD's bubbleable metadata.
   *
   * @see hook_schemadotorg_jsonld_schema_type_entity_alter()
   */
  public function schemaTypeEntityAlter(array &$data, EntityInterface $entity, ?SchemaDotOrgMappingInterface $mapping, ?BubbleableMetadata $bubbleable_metadata): void;

}
