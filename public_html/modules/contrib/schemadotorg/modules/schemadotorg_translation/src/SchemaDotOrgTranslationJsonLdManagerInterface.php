<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_translation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org translation JSON-LD manager interface.
 */
interface SchemaDotOrgTranslationJsonLdManagerInterface {

  /**
   * Alter Schema.org JSON-LD for an entity.
   *
   * @param array $data
   *   Schema.org type data.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The entity's Schema.org mapping.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   Object to collect JSON-LD's bubbleable metadata.
   *
   * @see hook_schemadotorg_jsonld_schema_type_entity_alter()
   */
  public function schemaTypeEntityAlter(array &$data, EntityInterface $entity, ?SchemaDotOrgMappingInterface $mapping, ?BubbleableMetadata $bubbleable_metadata): void;

}
