<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_node;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * The Schema.org bode manager.
 */
class SchemaDotOrgNodeManager implements SchemaDotOrgNodeManagerInterface {

  /**
   * Constructs a SchemaDotOrgManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    $entity_type_id = $mapping->getTargetEntityTypeId();
    if ($entity_type_id !== 'node') {
      return;
    }

    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $mapping->getTargetEntityBundleEntity();

    $config = $this->configFactory->get('schemadotorg_node.settings');
    $parts = [
      'schema_type' => $mapping->getSchemaType(),
      'bundle' => $mapping->getTargetBundle(),
    ];
    $node_type_updated = FALSE;

    // Set display author and date information.
    $display_submitted = $this->schemaTypeManager->getSetting($config->get('display_submitted'), $parts);
    if (!is_null($display_submitted)) {
      $node_type->setDisplaySubmitted($display_submitted);
      $node_type_updated = TRUE;
    }

    // Set menu settings.
    $menu_ui = $this->schemaTypeManager->getSetting($config->get('menu_ui'), $parts);
    if ($menu_ui && $this->moduleHandler->moduleExists('menu_ui')) {
      if (isset($menu_ui['available_menus'])) {
        $node_type->setThirdPartySetting('menu_ui', 'available_menus', $menu_ui['available_menus']);
      }
      if (isset($menu_ui['parent'])) {
        $node_type->setThirdPartySetting('menu_ui', 'parent', $menu_ui['parent']);
      }
      $node_type_updated = TRUE;
    }

    // If the node type has been update, resave it.
    if ($node_type_updated) {
      $node_type->save();
    }
  }

}
