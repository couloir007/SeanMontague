<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_block_content;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The Schema.org block content JSON-LD manager.
 */
class SchemaDotOrgBlockContentJsonLdManager implements SchemaDotOrgBlockContentJsonLdManagerInterface {

  /**
   * Constructs a SchemaDotOrgBlockContentJsonLdManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\block\BlockRepositoryInterface $blockRepository
   *   The block repository.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface|null $schemaJsonldBuilder
   *   The Schema.org JSON-LD builder.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BlockRepositoryInterface $blockRepository,
    #[Autowire(service: 'schemadotorg_jsonld.builder')]
    protected SchemaDotOrgJsonLdBuilderInterface|null $schemaJsonldBuilder = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonLd(RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): ?array {

    $data = [];
    foreach ($this->blockRepository->getVisibleBlocksPerRegion() as $blocks) {
      /** @var \Drupal\block\BlockInterface[] $blocks */
      foreach ($blocks as $block) {
        $block_plugin_id = $block->getPlugin()->getPluginId();
        $block_content = $this->loadBlockByPluginId($block_plugin_id);
        $block_content_data = $this->schemaJsonldBuilder->buildEntity(
          entity: $block_content,
          bubbleable_metadata: $bubbleable_metadata,
        );
        if ($block_content_data) {
          $data[$block_content->uuid()] = $block_content_data;
        }
      }
    }
    return $data;
  }

  /**
   * Load the content block entity associate with a block's plugin id.
   *
   * @param string $block_plugin_id
   *   A block's plugin id.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   *   A content block entity or NULL if there is not content block associated
   *   with the specified block's plugin id.
   */
  protected function loadBlockByPluginId(string $block_plugin_id): ?BlockContentInterface {
    if (!str_starts_with($block_plugin_id, 'block_content:')) {
      return NULL;
    }

    $block_content_uuid = str_replace('block_content:', '', $block_plugin_id);

    // Load the associated content block via UUID.
    $block_contents = $this->entityTypeManager
      ->getStorage('block_content')
      ->loadByProperties(['uuid' => $block_content_uuid]);
    if (empty($block_contents)) {
      return NULL;
    }

    /** @var \Drupal\block_content\BlockContentInterface $block_content */
    $block_content = reset($block_contents);
    return $block_content;
  }

}
