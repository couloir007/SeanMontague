<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\embedded_content\EmbeddedContentInterface;

/**
 * Interface for Schema.org Blueprints embedded content.
 */
interface SchemaDotOrgEmbeddedContentInterface extends EmbeddedContentInterface, ContainerFactoryPluginInterface {

  /**
   * Get embedded content's JSON-LD.
   *
   * @return array
   *   The embedded content's JSON-LD.
   */
  public function getJsonId(): array;

}
