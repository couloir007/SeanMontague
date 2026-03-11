<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_block_content_status;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;

/**
 * The Schema.org Block content status manager.
 */
class SchemaDotOrgBlockContentStatusManager implements SchemaDotOrgBlockContentStatusManagerInterface {
  use StringTranslationTrait;
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgBlockContentStatusManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function blockViewAlter(array &$build, BlockPluginInterface $block): void {
    $block_plugin_id = $block->getPluginId();
    if (!str_starts_with($block_plugin_id, 'block_content:')) {
      return;
    }

    $block_content_uuid = str_replace('block_content:', '', $block_plugin_id);

    // Load the associated content block via UUID.
    $block_contents = $this->entityTypeManager
      ->getStorage('block_content')
      ->loadByProperties(['uuid' => $block_content_uuid]);
    if (empty($block_contents)) {
      return;
    }

    /** @var \Drupal\block_content\BlockContentInterface $block_content */
    $block_content = reset($block_contents);
    $mapping = $this->getMappingStorage()->loadByEntity($block_content);
    if (!$mapping) {
      return;
    }

    $schema_type = $mapping->getSchemaType();
    $message_types = $this->configFactory
      ->get('schemadotorg_block_content_status.settings')
      ->get('message_types');
    $message_type = NULL;
    foreach ($message_types as $message_schema_type => $message_type) {
      if ($this->schemaTypeManager->isSubTypeOf($schema_type, $message_schema_type)) {
        break;
      }
      $message_type = NULL;
    }
    if (!$message_type) {
      return;
    }

    // Render the block within a message.
    $build = [
      '#theme' => 'status_messages',
      '#message_list' => [$message_type => [$build]],
      '#status_headings' => [
        'status' => $this->t('Status message'),
        'error' => $this->t('Error message'),
        'warning' => $this->t('Warning message'),
      ],
    ];

    // Add Schema.org block content settings as a cache dependency.
    $config = $this->configFactory->get('schemadotorg_block_content_status.settings');
    $this->renderer->addCacheableDependency($build, $config);
  }

}
