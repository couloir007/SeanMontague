<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_auto_entitylabel;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * The Schema.org auto entity label manager.
 */
class SchemaDotOrgAutoEntityLabelManager implements SchemaDotOrgAutoEntityLabelManagerInterface {

  /**
   * Constructs a SchemaDotOrgAutoEntityLabelManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    // Make sure the target entity type support bundling.
    // Comparing the entity type id and bundle is the easiest way to make this
    // determination. For example, for users the entity type and bundle are
    // the same.
    if ($mapping->getTargetEntityTypeId() === $mapping->getTargetBundle()) {
      return;
    }

    // Get the Schema.org mapping's auto entity label settings.
    $default_types = $this->configFactory
      ->get('schemadotorg_auto_entitylabel.settings')
      ->get('default_types');
    $settings = $this->schemaTypeManager->getSetting($default_types, $mapping);
    if (empty($settings)) {
      return;
    }

    // Get settings with default values.
    $values = $settings + [
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => '',
      'escape' => FALSE,
      'preserve_titles' => FALSE,
    ];

    // Get entity type and bundle.
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    // Replace pattern Schema.org properties with tokens.
    $pattern = $values['pattern'];
    $schema_properties = $mapping->getAllSchemaProperties();
    foreach ($schema_properties as $field_name => $schema_property) {
      $pattern = str_replace("[$schema_property]", "[$entity_type_id:$field_name:value]", $pattern);
    }
    $values['pattern'] = $pattern;

    // Set values in configuration.
    $config_name = 'auto_entitylabel.settings.' . $entity_type_id . '.' . $bundle;
    $config = $this->configFactory->getEditable($config_name);
    foreach ($values as $name => $value) {
      $config->set($name, $value);
    }

    // Set dependencies.
    // Look up the content entity's bundle entity's config prefix.
    $bundle_entity_type_id = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->getEntityType()
      ->getBundleEntityType();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $bundle_entity_type */
    $bundle_entity_type = $this->entityTypeManager
      ->getStorage($bundle_entity_type_id)
      ->getEntityType();
    $config_prefix = $bundle_entity_type->getConfigPrefix();
    $config->set('dependencies', ['config' => [$config_prefix . '.' . $bundle]]);

    // Save configuration.
    $config->save();
  }

}
