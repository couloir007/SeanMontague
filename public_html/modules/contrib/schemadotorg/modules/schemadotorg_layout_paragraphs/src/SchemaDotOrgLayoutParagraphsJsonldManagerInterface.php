<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_layout_paragraphs;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org layout paragraphs JSON-LD manager interface.
 */
interface SchemaDotOrgLayoutParagraphsJsonldManagerInterface {

  /**
   * Alter the Schema.org JSON-LD data for the current route.
   *
   * @param array $data
   *   The Schema.org JSON-LD data for the current route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Object to collect JSON-LD's bubbleable metadata.
   *
   * @see hook_schemadotorg_jsonld_alter()
   */
  public function alter(array &$data, RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): void;

}
