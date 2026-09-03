<?php

namespace Drupal\moderation_dashboard\Hook;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Views hooks implementations for moderation dashboard.
 */
class ModerationDashboardViewsHooks {

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data = [];

    $manager = $this->entityTypeManager;
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface[] $entity_types */
    $entity_types = array_filter($manager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return (
        $entity_type instanceof ContentEntityTypeInterface &&
        $entity_type->isRevisionable() &&
        $entity_type->hasHandlerClass('views_data')
      );
    });

    foreach ($entity_types as $id => $entity_type) {
      $table = $manager->getHandler($id, 'views_data')
        ->getViewsTableForEntityType($entity_type);

      $data[$table]['link_to_latest_version'] = [
        'title' => t('Link to latest version'),
        'field' => [
          'id' => 'link_to_latest_version',
          'real field' => $entity_type->getKey('id'),
        ],
      ];
    }

    return $data;
  }

  /**
   * Implements hook_preprocess_views_view().
   *
   * Don't show the pager if there's no reason to page. Might be fit for core.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(&$variables): void {
    if (isset($variables['id']) && str_starts_with($variables['id'], 'moderation_dashboard')) {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $variables['view'];
      if ($view->getCurrentPage() === 0 && $view->total_rows < $view->getItemsPerPage()) {
        $variables['pager'] = [];
      }
    }
  }

}
