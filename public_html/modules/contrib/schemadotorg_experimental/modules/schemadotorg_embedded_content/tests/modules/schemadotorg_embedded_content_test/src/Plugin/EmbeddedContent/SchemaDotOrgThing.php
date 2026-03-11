<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content_test\Plugin\EmbeddedContent;

use Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase;

/**
 * Thing - The most generic type of item.
 *
 * @EmbeddedContent(
 *   id = "schemadotorg_thing",
 *   label = @Translation("Thing"),
 *   description = @Translation("The most generic type of item."),
 * )
 */
class SchemaDotOrgThing extends SchemaDotOrgEmbeddedContentBase {

  /**
   * {@inheritdoc}
   */
  protected string $componentId = 'schemadotorg_embedded_content_test:thing';

  /**
   * {@inheritdoc}
   */
  protected string $schemaType = 'Thing';

  /**
   * {@inheritdoc}
   */
  protected array $schemaProperties = [
    'name',
    'description',
    'url',
  ];

}
