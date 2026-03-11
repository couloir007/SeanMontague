<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_epp\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg_epp\SchemaDotOrgEppManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Schema.org Blueprint Entity Prepopulate Node Links' block.
 */
#[Block(id: 'schemadotorg_epp_node_links',
  admin_label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Schema.org Blueprint Entity Prepopulate Node Links'),
  category: new \Drupal\Core\StringTranslation\TranslatableMarkup('Schema.org Blueprints'),
)]
class SchemaDotOrgEppNodeLinksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Schema.org Entity Prepopulate manager.
   */
  protected SchemaDotOrgEppManagerInterface $schemaEppManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaEppManager = $container->get('schemadotorg_epp.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'display' => SchemaDotOrgEppManagerInterface::DROPDOWN,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display as'),
      '#options' => [
        SchemaDotOrgEppManagerInterface::DROPDOWN => $this->t('Dropdown'),
        SchemaDotOrgEppManagerInterface::BUTTONS => $this->t('Buttons'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['display'],
      '#description' => $this->t('Select how entity prepopulate node links will be displayed on a node.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['display'] = $form_state->getValue('display');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): ?array {
    $node = $this->getCurrentNode();
    if (!$node) {
      return NULL;
    }

    return $this->schemaEppManager->buildNodeLinks($node, $this->configuration['display']) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeTags(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $cache_tags = parent::getCacheTags();

    // Make sure the block is updated as Schema.org mappings are created.
    $cache_tags = Cache::mergeTags($cache_tags, ['schemadotorg_mapping']);

    // Make sure the block is updated per node.
    $node = $this->getCurrentNode();
    if ($node) {
      $cache_tags = Cache::mergeTags($cache_tags, ['node:' . $node->id()]);
    }

    return $cache_tags;
  }

  /**
   * Get the current route's node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   the current route's node.
   */
  protected function getCurrentNode(): ?NodeInterface {
    $node = $this->routeMatch->getParameter('node');
    if (!$node) {
      return NULL;
    }
    elseif ($node instanceof NodeInterface) {
      return $node;
    }
    else {
      /** @var \Drupal\node\NodeInterface|null $node */
      $node = $this->entityTypeManager
        ->getStorage('node')
        ->load($node);
      return $node;
    }
  }

}
