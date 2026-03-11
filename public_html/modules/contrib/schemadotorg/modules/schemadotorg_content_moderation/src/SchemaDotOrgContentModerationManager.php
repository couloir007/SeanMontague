<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_content_moderation;

use Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * The Schema.org content moderation manager.
 */
class SchemaDotOrgContentModerationManager implements SchemaDotOrgContentModerationManagerInterface {

  /**
   * Constructs a SchemaDotOrgContentModerationManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    $schema_type = $mapping->getSchemaType();

    $default_workflows = $this->configFactory
      ->get('schemadotorg_content_moderation.settings')
      ->get('default_workflows');
    $default_workflow = $default_workflows["$entity_type_id--$schema_type"]
      ?? $default_workflows[$entity_type_id]
      ?? NULL;
    if (!$default_workflow) {
      return;
    }

    $workflow = $this->entityTypeManager
      ->getStorage('workflow')
      ->load($default_workflow);
    if (!$workflow) {
      return;
    }

    $content_moderation = $workflow->getTypePlugin();
    if (!$content_moderation instanceof ContentModerationInterface) {
      return;
    }

    $content_moderation->addEntityTypeAndBundle($entity_type_id, $bundle);
    $workflow->save();

    // Hide content moderation control widget from all view modes except full.
    $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_type_id, $bundle);
    unset($view_modes['full']);
    foreach (array_keys($view_modes) as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      $view_display->removeComponent('content_moderation_control');
      $view_display->save();
    }
  }

}
