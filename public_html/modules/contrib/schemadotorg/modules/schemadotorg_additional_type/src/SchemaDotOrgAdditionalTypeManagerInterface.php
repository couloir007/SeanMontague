<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org additional type interface.
 */
interface SchemaDotOrgAdditionalTypeManagerInterface {

  /**
   * Add new field mapping option.
   */
  const ADD_FIELD = SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD;

  /**
   * The additional type field name suffix.
   */
  const FIELD_NAME_SUFFIX = '_type';

  /**
   * Alter Schema.org mapping entity default values.
   *
   * @param array $defaults
   *   The Schema.org mapping entity default values.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @see hook_schemadotorg_mapping_defaults_alter()
   */
  public function mappingDefaultsAlter(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void;

  /**
   * Alter the Schema.org UI mapping form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterMappingForm(array &$form, FormStateInterface &$form_state): void;

  /**
   * Acts when creating a new entity.
   *
   * This hook runs after a new entity object has just been instantiated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param bool $override
   *   Override existing values.
   */
  public function entityCreate(EntityInterface $entity, bool $override = FALSE): void;

  /**
   * Prepares the node form.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param string $operation
   *   The operation being performed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function nodePrepareForm(NodeInterface $node, string $operation, FormStateInterface $form_state): void;

  /**
   * Alters the navigation.module's add content menu links.
   *
   * @param array &$links
   *   The discovered menu links to be altered.
   *
   * @see navigation_menu_links_discovered_alter()
   * @see \Drupal\navigation\NavigationContentLinks::addMenuLinks
   */
  public function menuLinksDiscoveredAlter(array &$links): void;

  /**
   * Alter the link variables.
   *
   * @param array $variables
   *   The link variables.
   */
  public function linkAlter(array &$variables): void;

  /**
   * Preprocess links variables.
   *
   * @param array $variables
   *   The links variables.
   */
  public function preprocessLinks(array &$variables): void;

  /**
   * Check if the additional type is required for a given Schema.org type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The Schema.org type.
   * @param string $schema_type
   *   The bundle name.
   *
   * @return bool
   *   TRUE if the additional type is required, FALSE otherwise.
   */
  public function isAdditionalTypeRequired(string $entity_type_id, ?string $bundle, string $schema_type): bool;

  /**
   * Determines if the additionalType property is required for a given content type.
   *
   * @param string $node_type
   *   A content type.
   *
   * @return bool
   *   TRUE if the additionalType property is required for the
   *   specified content type, otherwise FALSE.
   */
  public function isNodeTypeAdditionalTypeRequired(string $node_type): bool;

  /**
   * Determines if the "additionalType" mapping field allows multiple values.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping entity to check for the "additionalType" field.
   *
   * @return bool
   *   TRUE if the "additionalType" field supports multiple values, FALSE otherwise.
   */
  public function isMappingAdditionalTypeMultiple(SchemaDotOrgMappingInterface $mapping): bool;

  /**
   * Get the allowed values for the additional type field of a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping object.
   *
   * @return array|null
   *   An array of allowed values for the additional type field,
   *   or NULL if the field is not found.
   */
  public function getMappingAdditionalTypeAllowedValues(SchemaDotOrgMappingInterface $mapping): ?array;

  /**
   * Retrieves the default field values for specific schema type parts.
   *
   * @param array $parts
   *   An associative array of setting name parts
   *   which includes.
   *   - entity_type_id: The entity type id.
   *   - bundle: The entity bundle.
   *   - schema_type: The Schema.org type.
   *   - additional_type: The Schema.org additional type.
   *
   * @return array
   *   An associative array of default field values.
   */
  public function getDefaultFieldValues(array $parts): array;

}
