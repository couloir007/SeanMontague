<?php

namespace Drupal\mercury_editor\Controller;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\mercury_editor\MercuryEditorContextService;
use Drupal\mercury_editor\Ajax\IFrameAjaxResponseWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutRefreshTrait;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Class DuplicateController.
 *
 * Duplicates a component of a Layout Paragraphs Layout.
 * This is a copy of the DuplicateController class from the Layout Paragraphs
 * module.
 *
 * @todo Consider refactoring this class to extend the original class.
 */
class DuplicateController extends ControllerBase {

  use LayoutParagraphsLayoutRefreshTrait;
  use AjaxHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    protected LayoutParagraphsLayoutTempstoreRepository $tempstore,
    protected IFrameAjaxResponseWrapper $iFrameAjaxResponseWrapper,
    protected MercuryEditorContextService $mercuryEditorContext
    ) {}

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
   * Duplicates a component and returns appropriate response.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   * @param string $source_uuid
   *   The source component to be cloned.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   A build array or Ajax respone.
   */
  public function duplicate(LayoutParagraphsLayout $layout_paragraphs_layout, string $source_uuid) {
    $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
    $duplicate_component = $this->layoutParagraphsLayout->duplicateComponent($source_uuid);
    $this->tempstore->set($this->layoutParagraphsLayout);

    if ($this->isAjax()) {
      $response = new AjaxResponse();
      if ($this->needsRefresh()) {
        $layout = $this->renderLayout();
        $dom_selector = '[data-lpb-id="' . $this->layoutParagraphsLayout->id() . '"]';
        $this->iFrameAjaxResponseWrapper->addCommand(new ReplaceCommand($dom_selector, $layout));
        $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
        return $response;
      }
      $uuid = $duplicate_component->getEntity()->uuid();
      $rendered_item = [
        '#type' => 'layout_paragraphs_builder',
        '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
        '#uuid' => $uuid,
      ];
      $this->iFrameAjaxResponseWrapper->addCommand(new AfterCommand('[data-uuid="' . $source_uuid . '"]', $rendered_item));
      $this->iFrameAjaxResponseWrapper->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $uuid, 'component:update'));
      $response->addCommand($this->iFrameAjaxResponseWrapper->getWrapperCommand());
      return $response;
    }
    return [
      '#type' => 'layout_paragraphs_builder',
      '#layout_paragraphs_layout' => $layout_paragraphs_layout,
    ];

  }

}
