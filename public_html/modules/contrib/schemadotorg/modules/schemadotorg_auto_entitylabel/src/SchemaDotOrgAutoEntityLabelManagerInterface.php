<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_auto_entitylabel;

use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org auto entity label manager interface.
 */
interface SchemaDotOrgAutoEntityLabelManagerInterface {

  /**
   * Creates automatic entity label settings for a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   *
   * @see \Drupal\auto_entitylabel\Form\AutoEntityLabelForm::submitForm
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void;

}
