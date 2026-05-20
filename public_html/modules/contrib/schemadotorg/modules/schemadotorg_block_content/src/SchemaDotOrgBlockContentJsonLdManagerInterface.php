<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_block_content;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * The Schema.org block content JSON-LD manager interface.
 */
interface SchemaDotOrgBlockContentJsonLdManagerInterface {

  /**
   * Provide custom Schema.org JSON-LD data for a route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Object to collect JSON-LD's bubbleable metadata.
   *
   * @return array|null
   *   Custom Schema.org JSON-LD data.
   *
   * @see hook_schemadotorg_jsonld()
   */
  public function jsonLd(RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): ?array;

}
