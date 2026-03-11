<?php

namespace Drupal\mercury_editor\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_paragraphs\Utility\Dialog;
use Drupal\mercury_editor\Ajax\IFrameAjaxResponseWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;
use Drupal\layout_paragraphs\Form\DeleteComponentForm as LayoutParagraphsDeleteComponentForm;

/**
 * Class for deleting a component in Mercury Editor.
 */
class DeleteComponentForm extends LayoutParagraphsDeleteComponentForm {

  /**
   * {@inheritDoc}
   */
  protected function __construct(
    LayoutParagraphsLayoutTempstoreRepository $tempstore,
    protected IFrameAjaxResponseWrapper $iFrameAjaxResponseWrapper) {
    parent::__construct($tempstore);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('mercury_editor.iframe_ajax_response_wrapper'),
      $container->get('mercury_editor.context')
    );
  }

  /**
   * Ajax callback - deletes component and closes the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function deleteComponent(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand(Dialog::dialogSelector($this->layoutParagraphsLayout)));
    if ($this->needsRefresh()) {
      $layout = $this->renderLayout();
      $dom_selector = '[data-lpb-id="' . $this->layoutParagraphsLayout->id() . '"]';
      $this->iFrameAjaxResponseWrapper->addCommand(new ReplaceCommand($dom_selector, $layout));
      $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
      return $response;
    }
    $this->iFrameAjaxResponseWrapper->addCommand(new RemoveCommand('[data-uuid="' . $this->componentUuid . '"]'));
    $this->iFrameAjaxResponseWrapper->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $this->componentUuid, 'component:delete'));
    $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
    return $response;
  }

}
