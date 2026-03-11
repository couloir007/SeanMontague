<?php

namespace Drupal\mercury_editor\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\mercury_editor\MercuryEditorTempstore;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\mercury_editor\Ajax\IFrameAjaxResponseWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;
use Drupal\layout_paragraphs\Form\EditComponentForm as LayoutParagraphsEditComponentForm;

/**
 * Class for editing a component in Mercury Editor.
 */
class EditComponentForm extends LayoutParagraphsEditComponentForm {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    LayoutParagraphsLayoutTempstoreRepository $tempstore,
    EntityTypeManagerInterface $entity_type_manager,
    LayoutPluginManagerInterface $layout_plugin_manager,
    ModuleHandlerInterface $module_handler,
    EventDispatcherInterface $event_dispatcher,
    EntityRepositoryInterface $entity_repository,
    protected IFrameAjaxResponseWrapper $iFrameAjaxResponseWrapper,
    protected MercuryEditorTempstore $mercuryEditorTempstoreRepository
    ) {
    parent::__construct($tempstore, $entity_type_manager, $layout_plugin_manager, $module_handler, $event_dispatcher, $entity_repository);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.core.layout'),
      $container->get('module_handler'),
      $container->get('event_dispatcher'),
      $container->get('entity.repository'),
      $container->get('mercury_editor.iframe_ajax_response_wrapper'),
      $container->get('mercury_editor.tempstore_repository'),
      $container->get('mercury_editor.context')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $this->ajaxCloseForm($response);
    if ($this->needsRefresh()) {
      $layout = $this->renderLayout();
      $dom_selector = '[data-lpb-id="' . $this->layoutParagraphsLayout->id() . '"]';
      $this->iFrameAjaxResponseWrapper->addCommand(new ReplaceCommand($dom_selector, $layout));
      $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
      return $response;
    }

    $uuid = $this->paragraph->uuid();
    $rendered_item = $this->renderParagraph($uuid);

    $this->iFrameAjaxResponseWrapper->addCommand(new ReplaceCommand("[data-uuid={$uuid}]", $rendered_item));
    $this->iFrameAjaxResponseWrapper->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $uuid, 'component:update'));
    $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
    return $response;
  }

}
