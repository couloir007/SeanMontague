<?php

namespace Drupal\mercury_editor\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\mercury_editor\MercuryEditorTempstore;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mercury_editor\MercuryEditorContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EditTrayController.
 *
 * Refreshes the node being edited in the tray.
 */
class MercuryEditorController extends EntityViewController {

  use AjaxHelperTrait;
  use StringTranslationTrait;

  /**
   * The Mercury Editor Edit Tray tempstore service.
   *
   * @var \Drupal\mercury_editor\MercuryEditorTempstore
   */
  protected $tempstore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The mercury editor context service.
   *
   * @var \Drupal\mercury_editor\MercuryEditorContextService
   */
  protected $mercuryEditorContext;

  /**
   * Creates a NodeViewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\mercury_editor\MercuryEditorTempstore $tempstore
   *   The tempstore.
   * @param \Drupal\mercury_editor\MercuryEditorContextService $mercury_editor_context
   *   The mercury editor context service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, AccountInterface $current_user, EntityRepositoryInterface $entity_repository, MercuryEditorTempstore $tempstore, MercuryEditorContextService $mercury_editor_context) {
    parent::__construct($entity_type_manager, $renderer);
    $this->currentUser = $current_user;
    $this->entityRepository = $entity_repository;
    $this->tempstore = $tempstore;
    $this->mercuryEditorContext = $mercury_editor_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('mercury_editor.tempstore_repository'),
      $container->get('mercury_editor.context')
    );
  }

  /**
   * Edit an entity with the mercury editor edit tray.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Entity\ContentEntityInterface $mercury_editor_entity
   *   The entity.
   *
   * @return array
   *   The edit form.
   */
  public function editor(Request $request, ContentEntityInterface $mercury_editor_entity) {
    $form_state_additions = [];
    if ($request->get('translation_mode')) {
      $form_state_additions['langcode'] = $request->get('langcode');
      $form_state_additions['content_translation']['translation_form'] = $request->get('translation_form');
      if ($request->get('source')) {
        $form_state_additions['content_translation']['source'] = \Drupal::languageManager()->getLanguage($request->get('source'));
      }
      if ($request->get('target')) {
        $form_state_additions['content_translation']['target'] = \Drupal::languageManager()->getLanguage($request->get('target'));
      }
    }
    return \Drupal::service('entity.form_builder')->getForm($mercury_editor_entity, 'mercury_editor', $form_state_additions);
  }

  /**
   * Preview an entity being edited with the mercury editor edit tray.
   */
  public function preview() {
    $mercury_editor_entity = $this->mercuryEditorContext->getEntity();
    $this->mercuryEditorContext->setPreview(TRUE);
    $preview = $this->view($mercury_editor_entity);
    $preview['#attached']['drupalSettings']['mercuryEditor']['id'] = $mercury_editor_entity->uuid();
    $preview['#cache']['max-age'] = 0;
    $preview['#attached']['drupalSettings']['mercuryEditorId'] = $mercury_editor_entity->uuid();
    return $preview;
  }

  /**
   * Access callback for using mercury editor.
   */
  public function access() {
    $mercury_editor_entity = $this->mercuryEditorContext->getEntity();

    if ($mercury_editor_entity instanceof ContentEntityInterface) {
      return $mercury_editor_entity->id()
        ? $mercury_editor_entity->access('update', NULL, TRUE)
        : $mercury_editor_entity->access('create', NULL, TRUE);
    }

    return AccessResult::forbidden();
  }

}
