<?php

namespace Drupal\mercury_editor\Entity;

use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a trait for Mercury Editor entity forms.
 */
trait MercuryEditorEntityFormTrait {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStackInterface
   */
  protected $requestStack;


  /**
   * The mercury editor tray tempstore repository.
   *
   * @var \Drupal\mercury_editor\MercuryEditorTempstore
   */
  protected $tempstore;

  /**
   * The layout paragraphs tempstore repository.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $layoutParagraphsTempstore;

  /**
   * The iFrame Ajax Response Wrapper.
   *
   * @var \Drupal\mercury_editor\Ajax\IFrameAjaxResponseWrapper
   */
  protected $iFrameAjaxResponseWrapper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Mercury Editor context service.
   *
   * @var \Drupal\mercury_editor\MercuryEditorContextService
   */
  protected $mercuryEditorContextService;

  /**
   * The fields to sync changes in UI.
   *
   * @var array
   */
  protected $syncFields = ['name', 'title', 'label'];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->injectDependencies($container);
    return $instance;
  }

  /**
   * Injects dependencies from the container.
   */
  public function injectDependencies(ContainerInterface $container) {
    $this->tempstore = $container->get('mercury_editor.tempstore_repository');
    $this->layoutParagraphsTempstore = $container->get('layout_paragraphs.tempstore_repository');
    $this->iFrameAjaxResponseWrapper = $container->get('mercury_editor.iframe_ajax_response_wrapper');
    $this->entityTypeManager = $container->get('entity_type.manager');
    $this->mercuryEditorContextService = $container->get('mercury_editor.context');
    $this->requestStack = $container->get('request_stack');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = $this->mercuryEditorPrepareForm($form, $form_state);
    return $form;
  }

  /**
   * Applies necessary changes to the form for use with Mercury Editor.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered form array.
   */
  protected function mercuryEditorPrepareForm(array $form, FormStateInterface $form_state) {
    // Set default values for a new entity.
    // Specific Mercury Editor form classes should override this method to set
    // default values for the entity.
    if (!$form_state->get('init') && !$this->entity->id()) {
      $this->setDefaultEntityValues();
    }

    $form = parent::form($form, $form_state);
    $form['#theme'] = 'mercury_editor_entity_form';
    $label = $this->getEntityBundleTypeLabel();
    if ($this->entity->id()) {
      $form['#title'] = $this->t('Edit @type', [
        '@type' => $label,
      ]);
    }
    else {
      $form['#title'] = $this->t('Create @type', [
        '@type' => $label,
      ]);
    }
    $form['#attributes']['class'][] = 'me-node-form';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -100,
    ];

    // Provides a redirect URL so Javascript behaviors can redirect the parent
    // window to the correct destination after the editor is closed.
    $form['redirect_url'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => [
          'me-edit-screen-redirect-url',
        ],
      ],
      '#process' => [[$this, 'processRedirectUrl']],
    ];

    // @todo Make this more general, perhaps moving into another module.
    // Sync changes in UI.
    foreach ($this->syncFields as $field_name) {
      if (!empty($form[$field_name])) {
        $form[$field_name]['widget'][0]['value']['#attributes']['data-sync-changes'] = implode('/', [
          $this->entity->getEntityTypeId(),
          $this->entity->uuid(),
          $field_name,
        ]);
      }
    }

    // Temporarily save a reference to the layout paragraphs layout builder in
    // the entity so we can swap the field for the builder when rendering.
    $lp_storage_keys = [];
    foreach (Element::children($form) as $field_name) {
      if (isset($form[$field_name]['widget']['layout_paragraphs_storage_key'])) {
        $lp_storage_keys[$field_name] = $form[$field_name]['widget']['layout_paragraphs_storage_key']['#default_value'];
        // Hide the layout paragraphs field for rendering in the frontend.
        $form[$field_name]['widget']['layout_paragraphs_builder']['#access'] = FALSE;
        $form[$field_name]['widget']['#type'] = 'container';
        // Set the Mercury Editor context on the layout.
        /** @var \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout */
        $layout = $form[$field_name]['widget']['layout_paragraphs_builder']['#layout_paragraphs_layout'];

        // Set referring items so we can save the layout to the correct entity.
        $items = $layout->getParagraphsReferenceField();
        foreach ($items as $key => $item) {
          if (!empty($item->entity)) {
            $items[$key]->entity->_referringItem = $items[$key];
          }
        }
        $layout->setParagraphsReferenceField($items);

        $settings = $layout->getSettings();
        $settings['mercury_editor_context'] = TRUE;
        $settings['is_translating'] = $form[$field_name]['widget']['layout_paragraphs_builder']['#is_translating'] ?? FALSE;
        $layout->setSettings($settings);
        $this->layoutParagraphsTempstore->set($layout);
      }
    }
    if ($lp_storage_keys) {
      $this->entity->lp_storage_keys = $lp_storage_keys;
    }
    if (!$form_state->get('init')) {
      $form_state->set('init', TRUE);
    }
    $this->tempstore->set($this->entity);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) : array {

    $element = parent::actions($form, $form_state);
    if (isset($element['delete'])) {
      $element['delete']['#attributes']['target'] = '_self';
      $query = $element['delete']['#url']->getOption('query') ?? [];
      $element['delete']['#url']->setOption('query', ['_mercury_editor_iframe' => TRUE] + $query);
    }
    $element['submit']['#access'] = TRUE;
    $element['submit']['#ajax'] = [
      'callback' => '::ajaxCallback',
      'progress' => [
        'type' => 'mercuryeditor',
      ],
    ];
    $element['preview']['#access'] = FALSE;
    $element['#attributes']['class'][] = 'me-form-actions';

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('changed', time());
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // Reload the entity from storage to ensure all field values are correctly
    // populated before re-rendering the form.
    // @todo Consider moving this to a service as it is also used in
    // MercuryEditorController::editor().
    $lp_storage_keys = $this->entity->lp_storage_keys ?? [];
    $entity_type_id = $this->entity->getEntityTypeId();
    $entity_id = $this->entity->id();
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $revision_id = $storage->getLatestRevisionId($entity_id);
    $this->entity = !is_null($revision_id)
      ? $storage->loadRevision($revision_id)
      : $storage->load($entity_id);

    if ($this->entity instanceof ContentEntityInterface) {
      if ($form_state->get('langcode') !== $this->entity->language()->getId()) {
        $this->entity = $this->entity->getTranslation($form_state->get('langcode'));
      }
    }
    $this->entity->lp_storage_keys = $lp_storage_keys;
    $this->tempstore->set($this->entity);
    $this->messenger()->deleteAll();
    $this->messenger()->addStatus($this->t('@type has been updated.', [
      '@type' => $this->getEntityBundleTypeLabel(),
    ]));
    foreach (Element::children($form) as $field_name) {
      if (isset($form[$field_name]['widget']['layout_paragraphs_storage_key'])) {
        $key = $form[$field_name]['widget']['layout_paragraphs_storage_key']['#default_value'];
        $layout = $this->layoutParagraphsTempstore->getWithStorageKey($key);
        $layout->setParagraphsReferenceField($this->entity->$field_name);
        $this->layoutParagraphsTempstore->set($layout);
      }
    }
    // Clear the revision log message input to prevent it from being reused.
    $entity_type = $this->entity->getEntityType();
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $revision_field = $entity_type->getRevisionMetadataKey('revision_log_message');
      if ($revision_field) {
        $input = $form_state->getUserInput();
        $input[$revision_field] = [];
        $form_state->setUserInput($input);
      }
    }
    // Re-initialize the form to correctly prepare the saved entity.
    $this->init($form_state);
  }

  /**
   * Get the entity type label.
   *
   * @return string
   *   The label.
   */
  protected function getEntityBundleTypeLabel() {
    $type = $this->entity->getEntityType();
    $bundle_entity_type = $type->get('bundle_entity_type');
    if ($bundle_entity_type) {
      $bundle = $this->entity->bundle();
      $bundle_object = $this->entityTypeManager
        ->getStorage($bundle_entity_type)
        ->load($bundle);
      $label = $bundle_object ? $bundle_object->label() : '';
    }
    else {
      $label = $type->getLabel();
    }
    return $label;
  }

  /**
   * Ajax callback for rendering the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $form['#updated'] = TRUE;
    $response = new AjaxResponse();

    if (empty($form_state->getErrors())) {
      $selector = '[data-me-edit-screen-key="' . $this->entity->uuid() . '"]';
      $view_builder = $this->entityTypeManager->getViewBuilder($this->entity->getEntityTypeId());
      $langcode = $this->entity->language()->getId();
      $view = $view_builder->view($this->entity, 'full', $langcode);
      $this->iFrameAjaxResponseWrapper->addCommand(new ReplaceCommand($selector, $view));
      $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
    }
    else {
      $form['#attributes']['class'][] = 'unsaved-changes';
    }
    $response->addCommand(new ReplaceCommand('.me-node-form', $form));

    return $response;
  }

  /**
   * Process callback for setting the redirect URL.
   *
   * @param array $element
   *   The form element.
   * @param FormstateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed element.
   */
  public function processRedirectUrl(array $element, FormstateInterface $form_state) {
    $entity = $this->tempstore->get($this->entity->uuid());
    $request = $this->requestStack->getCurrentRequest();

    if ($request->get('destination')) {
      $element['#value'] = $request->get('destination');
    }
    elseif ($entity->id()) {
      if ($entity instanceof RevisionableInterface && ($entity->isDefaultRevision() || $entity->isLatestRevision())) {
        $element['#value'] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
      }
      else {
        $element['#value'] = $entity->toUrl('latest-version', ['absolute' => TRUE])->toString();
      }
    }
    else {
      $element['#value'] = Url::fromRoute('node.add_page', [], ['absolute' => TRUE])->toString();
    }

    return $element;
  }

  /**
   * Set default values for the entity.
   */
  public function setDefaultEntityValues() {
    if (method_exists(get_parent_class($this), 'setDefaultEntityValues')) {
      return parent::setDefaultEntityValues();
    }
  }

}
