<?php

namespace Drupal\mermaid_diagram_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds modal content for Mermaid Diagram field items.
 */
class MermaidModalController extends ControllerBase {

  /**
   * Renders a Mermaid diagram field item in a modal.
   *
   * @param string $entity_type
   *   The entity type ID (e.g., 'node', 'media').
   * @param int|string $entity_id
   *   The entity ID.
   * @param string $field_name
   *   The Mermaid field machine name.
   * @param int $delta
   *   The field item delta to render.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   Render array for the modal content.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the entity/field/delta is invalid.
   */
  public function view($entity_type, $entity_id, $field_name, $delta, Request $request) {
    // Load the host entity.
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($entity_type);
    $entity = $storage->loadRevision($storage->getLatestRevisionId($entity_id));
    if (
      !$entity instanceof ContentEntityInterface ||
      !$entity->hasField($field_name) ||
      !isset($entity->get($field_name)[$delta])
    ) {
      throw new NotFoundHttpException();
    }

    // Get the specific field item.
    $item = $entity->get($field_name)[$delta];

    $mermaid = $item->get('diagram')->getString();
    $title = $item->get('title')->getString();
    $key = $item->get('key')->getString();
    $caption = $item->get('caption')->getString();
    $show_code = $item->get('show_code')->getString();
    $allow_download = $item->get('allow_download')->getString();

    return [
      '#theme' => 'mermaid_diagram',
      '#mermaid' => $mermaid,
      '#title' => $title,
      '#key' => $key,
      '#caption' => $caption,
      '#show_code' => $show_code,
      '#allow_download' => $allow_download,
      '#field_name' => $field_name,
      '#entity_type' => $entity_type,
      '#bundle' => $entity->bundle(),
      '#attached' => [
        'library' => ['mermaid_diagram_field/diagram'],
      ],
    ];
  }

}
