<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_field_validation;

use Drupal\Core\Field\FieldConfigInterface;

/**
 * The Schema.org field validation manager interface.
 */
interface SchemaDotOrgFieldValidationManagerInterface {

  /**
   * Act on field config when it is inserted.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   Field config.
   */
  public function fieldConfigInsert(FieldConfigInterface $field_config): void;

}
