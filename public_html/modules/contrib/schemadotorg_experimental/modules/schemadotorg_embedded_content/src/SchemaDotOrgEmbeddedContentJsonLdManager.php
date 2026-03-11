<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;

/**
 * Schema.org embedded content JSON-LD manager.
 */
class SchemaDotOrgEmbeddedContentJsonLdManager implements SchemaDotOrgEmbeddedContentJsonLdManagerInterface {

  /**
   * Constructs a SchemaDotOrgEmbeddedContentJsonLdManager object.
   */
  public function __construct(
    protected SchemaDotOrgJsonLdManagerInterface $schemaJsonldManager,
    protected SchemaDotOrgEmbeddedJsonLdBuilderInterface $schemaEmbeddedContentJsonldBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaDotOrgJsonld(RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): ?array {
    $entity = $this->schemaJsonldManager->getRouteMatchEntity($route_match);
    return ($entity)
      ? $this->schemaEmbeddedContentJsonldBuilder->build($entity)
      : [];
  }

}
