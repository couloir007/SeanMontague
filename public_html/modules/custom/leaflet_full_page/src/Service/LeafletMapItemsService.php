<?php

namespace Drupal\leaflet_full_page\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Views;
use Drupal\Core\Render\RendererInterface;

/**
 * Service for handling leaflet full page map items data.
 */
class LeafletMapItemsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a LeafletMapItemsService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Gets processed map items from a named view display.
   *
   * @param string $current_display
   *   The view display machine name to execute.
   * @param array $field_names
   *   Optional field names to render into each item's 'fields' key.
   * @param string $view_mode
   *   The view mode for field rendering.
   *
   * @return array
   *   Array of item arrays with 'id', 'title', 'type', and optionally 'fields'.
   */
  public function getProcessedMapItems(string $current_display, array $field_names = [], string $view_mode = 'default'): array {
    $view = Views::getView('leaflet_full_page');
    if (!$view) {
      return [];
    }

    $view->setDisplay($current_display);
    $view->execute();

    $map_items = [];

    foreach ($view->result as $row) {
      if (!isset($row->_entity)) {
        continue;
      }
      $entity = $row->_entity;

      $item = [
        'id' => $entity->id(),
        'title' => $entity->label(),
        'type' => $entity->getEntityTypeId(),
      ];

      foreach ($field_names as $field_name) {
        $item['fields'][$field_name] = $this->renderEntityField($entity, $field_name, $view_mode);
      }

      $map_items[] = $item;
    }

    return $map_items;
  }

  /**
   * Renders a field from an entity.
   */
  protected function renderEntityField($entity, string $field_name, string $view_mode = 'default'): string {
    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return '';
    }
    $field = $entity->get($field_name);
    $build = $field->view($view_mode);
    return $this->renderer->render($build);
  }

  /**
   * Renders a media entity field using a specific view mode.
   */
  protected function renderMediaField($entity, string $field_name, string $view_mode = 'default'): string {
    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return '';
    }
    $media_entity = $entity->get($field_name)->entity;
    if (!$media_entity) {
      return '';
    }
    $view_builder = $this->entityTypeManager->getViewBuilder('media');
    $build = $view_builder->view($media_entity, $view_mode);
    return $this->renderer->render($build);
  }

}
