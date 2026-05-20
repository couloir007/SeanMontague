<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_block_content_status;

use Drupal\Core\Block\BlockPluginInterface;

/**
 * The Schema.org Block content status manager interface.
 */
interface SchemaDotOrgBlockContentStatusManagerInterface {

  /**
   * Alters the rendering of a block to include messaging.
   *
   * @param array $build
   *   The renderable array for the block content.
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin instance being rendered.
   */
  public function blockViewAlter(array &$build, BlockPluginInterface $block): void;

}
