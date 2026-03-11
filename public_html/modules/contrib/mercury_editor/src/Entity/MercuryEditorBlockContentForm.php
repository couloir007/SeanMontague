<?php

namespace Drupal\mercury_editor\Entity;

use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\block_content\BlockContentForm;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Mercury Editor block content form.
 */
class MercuryEditorBlockContentForm extends BlockContentForm {

  use MercuryEditorEntityFormTrait;

  /**
   * The plugin.manager.block service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Injects dependencies from the container.
   */
  public function injectDependencies(ContainerInterface $container) {
    $this->tempstore = $container->get('mercury_editor.tempstore_repository');
    $this->layoutParagraphsTempstore = $container->get('layout_paragraphs.tempstore_repository');
    $this->iFrameAjaxResponseWrapper = $container->get('mercury_editor.iframe_ajax_response_wrapper');
    $this->entityTypeManager = $container->get('entity_type.manager');
    $this->pluginManagerBlock = $container->get('plugin.manager.block');
    $this->mercuryEditorContextService = $container->get('mercury_editor.context');
    $this->contextRepository = $container->get('context.repository');
    $this->contextHandler = $container->get('context.handler');
    $this->routeMatch = $container->get('current_route_match');
    $this->titleResolver = $container->get('title_resolver');
    $this->account = $container->get('current_user');
  }

  /**
   * {@inheritDoc}
   */
  public function setDefaultEntityValues() {
    $this->entity->name = 'New block';
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
      $view = $this->buildBlock('block_content:' . $this->entity->uuid());
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
   * Returns a block render array.
   *
   * @param string $id
   *   The block id.
   * @param array $configuration
   *   The block configuration.
   * @param bool $wrapper
   *   Whether or not use block template for rendering.
   *
   * @return array
   *   The built block.
   */
  protected function buildBlock(string $id, array $configuration = [], bool $wrapper = TRUE) {

    $is_preview = $this->mercuryEditorContextService->isPreview();
    $this->mercuryEditorContextService->setPreview(TRUE);

    $configuration += ['label_display' => BlockPluginInterface::BLOCK_LABEL_VISIBLE];
    /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
    $block_plugin = $this->pluginManagerBlock->createInstance($id, $configuration);

    // Inject runtime contexts.
    if ($block_plugin instanceof ContextAwarePluginInterface) {
      $contexts = $this->contextRepository->getRuntimeContexts($block_plugin->getContextMapping());
      $this->contextHandler->applyContextMapping($block_plugin, $contexts);
    }

    $build = [];
    $access = $block_plugin->access($this->account, TRUE);
    if ($access->isAllowed()) {
      // Title block needs a special treatment.
      if ($block_plugin instanceof TitleBlockPluginInterface) {
        // Account for the scenario that a NullRouteMatch is returned. This, for
        // example, is the case when Search API is indexing the site during
        // Drush cron.
        if ($route = $this->routeMatch->getRouteObject()) {
          $request = $this->requestStack->getCurrentRequest();
          $title = $this->titleResolver->getTitle($request, $route);
          $block_plugin->setTitle($title);
        }
      }

      // Place the content returned by the block plugin into a 'content' child
      // element, as a way to allow the plugin to have complete control of its
      // properties and rendering (for instance, its own #theme) without
      // conflicting with the properties used above.
      $build['content'] = $block_plugin->build();

      if ($block_plugin instanceof TitleBlockPluginInterface) {
        $build['content']['#cache']['contexts'][] = 'url';
      }
      // Some blocks return null instead of array when empty.
      // @see https://www.drupal.org/project/drupal/issues/3212354
      if ($wrapper && is_array($build['content']) && !Element::isEmpty($build['content'])) {
        $build += [
          '#theme' => 'block',
          '#id' => $configuration['id'] ?? NULL,
          '#attributes' => [],
          '#contextual_links' => [],
          '#configuration' => $block_plugin->getConfiguration(),
          '#plugin_id' => $block_plugin->getPluginId(),
          '#base_plugin_id' => $block_plugin->getBaseId(),
          '#derivative_plugin_id' => $block_plugin->getDerivativeId(),
        ];
        // Semantically, the content returned by the plugin is the block, and in
        // particular, #attributes and #contextual_links is information about
        // the *entire* block. Therefore, we must move these properties into the
        // top-level element.
        foreach (['#attributes', '#contextual_links'] as $property) {
          if (isset($build['content'][$property])) {
            $build[$property] = $build['content'][$property];
            unset($build['content'][$property]);
          }
        }
      }
    }

    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($access)
      ->addCacheableDependency($block_plugin)
      ->applyTo($build);

    if (!isset($build['#cache']['keys'])) {
      $build['#cache']['keys'] = [
        'mercury_editor',
        $id,
        '[configuration]=' . hash('sha256', serialize($configuration)),
        '[wrapper]=' . (int) $wrapper,
      ];
    }

    $this->mercuryEditorContextService->setPreview($is_preview);
    return $build;
  }

}
