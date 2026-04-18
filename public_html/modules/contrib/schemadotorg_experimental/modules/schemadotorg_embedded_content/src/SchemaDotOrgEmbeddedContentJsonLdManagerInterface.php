<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org embedded content JSON-LD manager interface.
 */
interface SchemaDotOrgEmbeddedContentJsonLdManagerInterface {

  /**
   * Generates Schema.org JSON-LD data for embedded content based on the current route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object used to determine the relevant entity.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata object used for caching and dependencies.
   *
   * @return array|null
   *   An array of JSON-LD data if an entity is found, or null if no entity is available.
   *
   * @see \Drupal\embedded_content\Plugin\Filter\EmbeddedContent::process
   * @see \Drupal\media\Plugin\Filter\MediaEmbed::process
   */
  public function schemaDotOrgJsonld(RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): ?array;

}
