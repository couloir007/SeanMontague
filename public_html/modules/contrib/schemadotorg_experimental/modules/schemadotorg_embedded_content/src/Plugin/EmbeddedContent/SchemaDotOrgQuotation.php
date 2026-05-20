<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content\Plugin\EmbeddedContent;

use Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase;

/**
 * Quotation - A quotation.
 *
 * @EmbeddedContent(
 *   id = "schemadotorg_quotation",
 *   label = @Translation("Quotation"),
 *   description = @Translation("A quotation."),
 * )
 */
class SchemaDotOrgQuotation extends SchemaDotOrgEmbeddedContentBase {

  /**
   * {@inheritdoc}
   */
  protected string $componentId = 'schemadotorg_components:quotation';

  /**
   * {@inheritdoc}
   */
  protected string $schemaType = 'Quotation';

  /**
   * {@inheritdoc}
   */
  protected array $schemaProperties = [
    'text',
    'spokenByCharacter',
  ];

}
