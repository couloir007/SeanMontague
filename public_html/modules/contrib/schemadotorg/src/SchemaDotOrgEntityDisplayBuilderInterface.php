<?php

declare(strict_types=1);

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Schema.org entity display builder interface.
 */
interface SchemaDotOrgEntityDisplayBuilderInterface {

  /**
   * Entity form/view display patterns.
   */
  const PATTERNS = [
    ['entity_type_id'],
    ['entity_type_id', 'display_type'],
    ['entity_type_id', 'display_type', 'bundle'],
    ['entity_type_id', 'display_type', 'bundle', 'field_name'],
    ['entity_type_id', 'display_type', 'schema_type'],
    ['entity_type_id', 'display_type', 'schema_type', 'schema_property'],
    ['entity_type_id', 'display_type', 'schema_property'],
    ['entity_type_id', 'display_type', 'field_name'],
    ['entity_type_id', 'display_type', 'display_mode'],
    ['entity_type_id', 'display_type', 'display_mode', 'bundle'],
    ['entity_type_id', 'display_type', 'display_mode', 'bundle', 'field_name'],
    ['entity_type_id', 'display_type', 'display_mode', 'field_name'],
    ['entity_type_id', 'display_type', 'display_mode', 'schema_type'],
    ['entity_type_id', 'display_type', 'display_mode', 'schema_type', 'schema_property'],
    ['entity_type_id', 'display_type', 'display_mode', 'schema_property'],
    ['entity_type_id', 'bundle'],
    ['entity_type_id', 'bundle', 'field_name'],
    ['entity_type_id', 'schema_type'],
    ['entity_type_id', 'schema_type', 'schema_property'],
    ['entity_type_id', 'schema_property'],
    ['entity_type_id', 'field_name'],
  ];

  /**
   * Hide component from entity display.
   */
  const COMPONENT_HIDDEN = 'schemadotorg_component_hidden';

  /**
   * Gets default field weights.
   *
   * @return array
   *   An array containing default field weights.
   */
  public function getDefaultFieldWeights(): array;

  /**
   * Get the default field weight for Schema.org property.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The Schema.org property.
   * @param string $field_name
   *   The field name.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return int
   *   The default field weight for Schema.org property.
   */
  public function getSchemaPropertyDefaultFieldWeight(string $entity_type_id, string $bundle, string $field_name, string $schema_type, string $schema_property): int;

  /**
   * Initialize all form and view displays for a new Schema.org mapping.
   *
   * This method saves all form and view displays for a new Schema.org mapping
   * with a $display->schemaDotOrgType = 'SchemaType'; defined;
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   A new Schema.org mapping.
   */
  public function initializeDisplays(SchemaDotOrgMappingInterface $mapping): void;

  /**
   * Retrieves and filters the display component weights for updates.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The display object from which the weights are retrieved and filtered.
   *
   * @return array|null
   *   An associative array of component weights indexed by their names, or NULL
   *   if no weights are available.
   */
  public function getDisplayComponentWeights(EntityDisplayInterface $display): ?array;

  /**
   * Alters the entity display edit form by modifying component weights and human-readable names.
   *
   * @param array $form
   *   The renderable form array for the entity display edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterEntityDisplayEditForm(array &$form, FormStateInterface $form_state): void;

  /**
   * Updates the default component weights for an entity display.
   *
   * @param\Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display object that needs to be updated.
   */
  public function updateDisplayComponentWeights(EntityDisplayInterface $display): void;

  /**
   * Set the display settings for a field.
   *
   * @param array $field
   *   The field definition.
   * @param string|null $widget_id
   *   The widget ID.
   * @param array $widget_settings
   *   The settings for the widget.
   * @param string|null $formatter_id
   *   The formatter ID.
   * @param array $formatter_settings
   *   The settings for the formatter.
   */
  public function setFieldDisplays(
    array $field,
    ?string $widget_id,
    array $widget_settings,
    ?string $formatter_id,
    array $formatter_settings,
  ): void;

  /**
   * Set the default component weights for a Schema.org mapping entity.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function setComponentWeights(SchemaDotOrgMappingInterface $mapping): void;

  /**
   * Get display modes for a specific entity display.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   *
   * @return array
   *   An array of display modes.
   */
  public function getModes(EntityDisplayInterface $display): array;

  /**
   * Get display form modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   An array of display form modes.
   */
  public function getFormModes(string $entity_type_id, string $bundle): array;

  /**
   * Get display view modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   An array of display view modes.
   */
  public function getViewModes(string $entity_type_id, string $bundle): array;

}
