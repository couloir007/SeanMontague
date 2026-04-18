<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org embedded content JSON-LD manager. interface.
 */
interface SchemaDotOrgEmbeddedJsonLdBuilderInterface {

  /**
   * Build embedded content JSON-LD data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The embedded content JSON-LD data.
   */
  public function build(EntityInterface $entity): array;

}
