<?php

namespace Drupal\mermaid_diagram_field\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a mermaid_diagram field mapper.
 *
 * @FeedsTarget(
 *   id = "mermaid_feeds_target",
 *   field_types = {"mermaid_diagram"}
 * )
 */
class MermaidField extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    /** @phpstan-ignore-next-line */
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition);
    $definition
      ->addProperty('title')
      ->addProperty('diagram')
      ->addProperty('caption')
      ->addProperty('key')
      ->addProperty('show_code')
      ->addProperty('allow_download');
    return $definition;
  }

}
