<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_jsonld_breadcrumb;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * The Schema.org JSON-LD breadcrumb manager interface.
 */
interface SchemaDotOrgJsonLdBreadcrumbManagerInterface {

  /**
   * Generates JSON-LD structured data for breadcrumb based on provided route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata object.
   *
   * @return array|null
   *   The JSON-LD structured data for breadcrumb list or NULL if no breadcrumb links are found.
   *
   * @see hook_schemadotorg_jsonld()
   */
  public function jsonLd(RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): ?array;

  /**
   * Alter the JSON-LD Breadcrumb data.
   *
   * @param array $data
   *   The JSON-LD data to be modified.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata object.
   */
  public function jsonLdAlter(array &$data, RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): void;

}
