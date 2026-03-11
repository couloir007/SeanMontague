<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_options;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;

/**
 * The Schema.org options JSON-LD manager.
 */
class SchemaDotOrgOptionsJsonLdManager implements SchemaDotOrgOptionsJsonLdManagerInterface {
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgOptionsJsonLdManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaPropertyAlter(mixed &$value, FieldItemInterface $item, BubbleableMetadata $bubbleable_metadata): void {
    if (!$item instanceof OptionsProviderInterface || is_array($value)) {
      return;
    }

    $entity = $item->getEntity();

    $mapping = $this->getMappingStorage()->loadByEntity($entity);

    // Convert option value to option text.
    if ($mapping) {
      $parts = [
        'schema_type' => $mapping->getSchemaType(),
        'schema_property' => $mapping->getSchemaPropertyMapping($item->getFieldDefinition()->getName()),
      ];
      $allowed_value_text = $this->configFactory
        ->get('schemadotorg_options.settings')
        ->get('allowed_value_text');
      if ($this->schemaTypeManager->getSetting($allowed_value_text, $parts)) {
        $allowed_values = options_allowed_values($item->getFieldDefinition()->getFieldStorageDefinition(), $entity);
        $value = $allowed_values[$value] ?? $value;
      }
    }

    // Convert value to URI.
    $uri = $this->configFactory
      ->get('schemadotorg_options.settings')
      ->get("allowed_value_uris.$value");
    if ($uri) {
      $value = $uri;
    }
  }

}
