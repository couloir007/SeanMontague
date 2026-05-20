<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_scheduler;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * The Schema.org scheduler JSON-LD manager.
 */
final class SchemaDotOrgSchedulerJsonLdManager implements SchemaDotOrgSchedulerJsonLdManagerInterface {

  /**
   * Constructs a SchemaDotOrgScheduleJsonLdManager object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The data formatter.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaTypeEntityLoad(array &$data, EntityInterface $entity, ?SchemaDotOrgMappingInterface $mapping, BubbleableMetadata $bubbleable_metadata): void {
    // Make sure this is a content entity with a mapping.
    if (!$entity instanceof ContentEntityInterface
      || !$mapping) {
      return;
    }

    $schema_type = $mapping->getSchemaType();
    $properties = [
      'datePublished' => 'publish_on',
      'expires' => 'unpublish_on',
    ];
    foreach ($properties as $property => $field) {
      if (empty($data[$property])
        && $this->schemaTypeManager->hasProperty($schema_type, $property)
        && $entity->hasField($field)
        && $entity->get($field)->value) {
        $data[$property] = $this->dateFormatter->format($entity->get($field)->value, 'custom', 'Y-m-d H:i:s P');
      }
    }
  }

}
