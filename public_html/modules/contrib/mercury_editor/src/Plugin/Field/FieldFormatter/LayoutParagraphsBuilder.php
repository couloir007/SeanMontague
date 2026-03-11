<?php

namespace Drupal\mercury_editor\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_paragraphs\LayoutParagraphsComponent;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Access\LayoutParagraphsBuilderAccess;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;
use Drupal\layout_paragraphs\Plugin\Field\FieldFormatter\LayoutParagraphsFormatter;
use Drupal\mercury_editor\MercuryEditorContextService;

/**
 * Layout Paragraphs Builder field formatter.
 *
 * @FieldFormatter(
 *   id = "mercury_editor_layout_paragraphs_builder",
 *   label = @Translation("Mercury Editor Layout Paragraphs Builder"),
 *   description = @Translation("Renders paragraphs with layout."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class LayoutParagraphsBuilder extends LayoutParagraphsFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LoggerChannelFactoryInterface $logger_factory,
    EntityDisplayRepositoryInterface $entity_display_repository,
    protected LayoutParagraphsBuilderAccess $layoutParagraphsBuilderAccess,
    protected LayoutParagraphsLayoutTempstoreRepository $tempstore,
    protected AccountProxyInterface $account,
    protected MercuryEditorContextService $mercuryEditorContextService) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_display_repository);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_display.repository'),
      $container->get('layout_paragraphs.builder_access'),
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('current_user'),
      $container->get('mercury_editor.context')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::viewElements($items, $langcode);
    if (!$this->mercuryEditorContextService->isPreview() && !$this->mercuryEditorContextService->isEditor()) {
      return $elements;
    }
    foreach ($items as $key => $item) {
      if (!empty($item->entity)) {
        $items[$key]->entity->_referringItem = $items[$key];
      }
    }
    $mercury_editor_entity = $this->mercuryEditorContextService->getEntity();
    $settings = $this->getSettings() + [
      'reference_field_view_mode' => $this->viewMode,
      'mercury_editor_context' => TRUE,
      'mercury_editor_uuid' => $mercury_editor_entity->uuid(),
    ];
    $layout = new LayoutParagraphsLayout($items, $settings);
    if (!$this->layoutParagraphsBuilderAccess->access($this->account, $layout)->isAllowed()) {
      return $elements;
    }
    $this->tempstore->set($layout);
    $build = [
      '#type' => 'layout_paragraphs_builder',
      '#layout_paragraphs_layout' => $layout,
    ];
    return [
      [
        'builder' => $build,
      ],
    ];
  }

}
