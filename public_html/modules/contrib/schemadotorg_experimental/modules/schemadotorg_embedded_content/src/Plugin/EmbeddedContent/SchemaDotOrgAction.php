<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content\Plugin\EmbeddedContent;

use Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase;

/**
 * Action - An action performed by a direct agent and indirect participants upon a direct object.
 *
 * @EmbeddedContent(
 *   id = "schemadotorg_action",
 *   label = @Translation("Action"),
 *   description = @Translation("An action performed by a direct agent and indirect participants upon a direct object"),
 * )
 */
class SchemaDotOrgAction extends SchemaDotOrgEmbeddedContentBase {

  /**
   * {@inheritdoc}
   */
  protected string $componentId = 'schemadotorg_components:action';

  /**
   * {@inheritdoc}
   */
  protected string $schemaType = 'Action';

  /**
   * {@inheritdoc}
   */
  protected array $schemaProperties = [
    'type',
    'name',
    'url',
  ];

}
