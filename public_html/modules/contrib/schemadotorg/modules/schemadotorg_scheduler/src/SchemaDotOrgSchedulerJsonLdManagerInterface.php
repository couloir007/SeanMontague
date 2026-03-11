<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_scheduler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org scheduler JSON-LD manager interface.
 */
interface SchemaDotOrgSchedulerJsonLdManagerInterface {

  /**
   * Load the Schema.org type JSON-LD data for an entity.
   *
   * @param array $data
   *   The Schema.org JSON-LD data for an entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The entity's Schema.org mapping.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Object to collect JSON-LD's bubbleable metadata.
   */
  public function schemaTypeEntityLoad(array &$data, EntityInterface $entity, ?SchemaDotOrgMappingInterface $mapping, BubbleableMetadata $bubbleable_metadata): void;

}
