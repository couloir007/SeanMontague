<?php

namespace Drupal\mercury_editor;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Provides a service for Mercury Editor context.
 */
class MercuryEditorContextService {

  /**
   * Whether the current route is a "Mercury Editor" preview.
   *
   * @var bool|null
   *  NULL if the preview state is not yet determined.
   */
  protected $preview = NULL;

  /**
   * MercuryEditorContextService constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository $layoutParagraphsTempstore
   *   The layout paragraphs layout tempstore repository.
   * @param \Drupal\mercury_editor\MercuryEditorTempstore $mercuryEditorTempstore
   *   The mercury editor tempstore.
   * @param \Drupal\Core\Routing\RequestStack $requestStack
   *   The request.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected LayoutParagraphsLayoutTempstoreRepository $layoutParagraphsTempstore,
    protected MercuryEditorTempstore $mercuryEditorTempstore,
    protected RequestStack $requestStack,
  ) {}

  /**
   * Determines if the current route is a Mercury Editor preview route.
   *
   * @return bool
   *   Returns TRUE if the current route is a Mercury Editor preview, else
   *   FALSE.
   */
  public function isPreview(): bool {
    // Check if the preview state is already explicitly set.
    if ($this->preview !== NULL) {
      return $this->preview;
    }

    if ($this->isPreviewRequest()) {
      $this->preview = TRUE;
      return $this->preview;
    }

    if ($this->isPreviewRoute() !== NULL) {
      $this->preview = $this->isPreviewRoute();
      return $this->preview;
    }

    return (bool) $this->preview;
  }

  /**
   * Determines if the current route is a Mercury Editor preview route.
   *
   * @return bool|null
   *   Returns TRUE if the current route is a Mercury Editor preview route,
   */
  public function isPreviewRoute(): ?bool {
    $route_name = $this->routeMatch->getRouteName();

    // This check can happen before the route match is set,
    // so we don't know for sure if it's true or not.
    if (empty($route_name)) {
      return NULL;
    }

    return str_ends_with($route_name, '.mercury_editor_preview');
  }

  /**
   * Determines if the current request has Mercury Editor search param.
   *
   * @return bool
   *   Returns TRUE if the current request has 'me_id' query param, else FALSE.
   */
  public function isPreviewRequest(): bool {
    $request = $this->requestStack->getCurrentRequest();
    return $request->query->has('me_id');
  }

  /**
   * Sets the preview state for the current Mercury Editor context.
   *
   * @param bool $preview
   *   The preview state, true if the current context is a preview.
   *
   * @return $this
   *   The current instance.
   */
  public function setPreview(bool $preview = TRUE) {
    $this->preview = $preview;
    return $this;
  }

  /**
   * Determines if the current route is a "Mercury Editor" editor.
   *
   * The method checks the route name retrieved from the route match object and
   * compares it to 'mercury_editor.editor'. If the route name is exactly
   * 'mercury_editor.editor', the method returns true, indicating that the
   * current route is indeed for a "Mercury Editor" editor. Otherwise, it
   * returns false.
   *
   * @return bool
   *   Returns TRUE if the current route is for a "Mercury Editor" editor; FALSE
   *   otherwise.
   */
  public function isEditor(): bool {
    $route_name = $this->routeMatch->getRouteName();
    return $route_name === 'mercury_editor.editor';
  }

  /**
   * Returns the mercury editor preview entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The mercury editor entity.
   */
  public function getEntity(): ?ContentEntityInterface {
    if ($this->requestStack->getCurrentRequest()->query->has('me_id')) {
      $entity = $this->mercuryEditorTempstore->get($this->requestStack->getCurrentRequest()->query->get('me_id'));
      return $entity;
    }
    if ($this->isEditor()) {
      return $this->routeMatch->getParameter('mercury_editor_entity');
    }
    if ($this->isPreview()) {
      $route_name = $this->routeMatch->getRouteName();
      if ($route_name === 'mercury_editor.preview') {
        return $this->routeMatch->getParameter('entity');
      }
      if (str_ends_with($route_name, '.mercury_editor_preview')) {
        return $this->routeMatch->getParameter(explode('.', $route_name)[1]);
      }
    }
    return NULL;
  }

  /**
   * Sets the mercury editor entity in the tempstore.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function setEntity(ContentEntityInterface $entity) {
    $this->mercuryEditorTempstore->set($entity);
  }

  /**
   * Saves a layout to the parent Mercury Editor Entity in the tempstore.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout paragraphs layout object.
   */
  public function saveLayout(LayoutParagraphsLayout $layout) {

    $entity = $layout->getEntity();
    $item_list = $layout->getParagraphsReferenceField();

    while ($entity->_referringItem) {
      $field_name = $item_list->getName();
      $entity->$field_name = $item_list;
      $item_list = $entity->_referringItem->getParent();
      $parent_entity = $item_list->getEntity();
      if ($mercury_editor_entity = $this->mercuryEditorTempstore->get($parent_entity->uuid())) {
        $storage_key = $mercury_editor_entity->lp_storage_keys[$field_name];
        $layout = \Drupal::service('layout_paragraphs.tempstore_repository')->getWithStorageKey($storage_key);
        $layout->setParagraphsReferenceField($item_list);
        $mercury_editor_entity->$field_name = $item_list;
        $this->layoutParagraphsTempstore->set($layout);
        $this->mercuryEditorTempstore->set($mercury_editor_entity);
        $parent_entity = $mercury_editor_entity;
      }
      $entity = $parent_entity;
    }
  }

  /**
   * Recursively finds the child entity and saves it.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $reference_field
   *   The paragraph entity.
   * @param string $uuid
   *   The uuid of the entity.
   */
  protected function recursivelyFindChild(EntityReferenceFieldItemListInterface &$reference_field, string $uuid) {

    foreach ($reference_field as &$item) {
      if ($item->entity->uuid() == $uuid) {
        return $item->entity;
      }
      $definitions = array_filter(
        $item->entity->getFieldDefinitions(),
        function ($defintion) {
          return $defintion->getType() == 'entity_reference_revisions';
        }
      );
      foreach ($definitions as $definition) {
        $field_name = $definition->getName();
        $reference_field =& $item->entity->$field_name;
        return $this->recursivelyFindChild($reference_field, $uuid);
      }
    }
  }

}
