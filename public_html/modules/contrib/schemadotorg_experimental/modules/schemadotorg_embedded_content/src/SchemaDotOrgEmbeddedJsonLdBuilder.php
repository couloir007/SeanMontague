<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\embedded_content\EmbeddedContentPluginManager;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Schema.org embedded content JSON-LD builder.
 *
 * @see \Drupal\embedded_content\Plugin\Filter\EmbeddedContent
 */
class SchemaDotOrgEmbeddedJsonLdBuilder implements SchemaDotOrgEmbeddedJsonLdBuilderInterface {
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgEmbeddedJsonLdBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\embedded_content\EmbeddedContentPluginManager $embeddedContentManager
   *   The embedded content manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface|null $schemaJsonLdBuilder
   *   The Schema.org JSON-LD builder service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'plugin.manager.embedded_content')]
    protected EmbeddedContentPluginManager $embeddedContentManager,
    protected ?SchemaDotOrgJsonLdBuilderInterface $schemaJsonLdBuilder = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if (!$mapping) {
      return [];
    }

    // Make sure the entity's values includes the <embedded-content /> tag.
    $text = print_r($entity->toArray(), TRUE);
    if (!str_contains($text, '<embedded-content')) {
      return [];
    }

    $data = [];
    $schema_properties = $mapping->getSchemaProperties();
    foreach ($schema_properties as $field_name => $schema_property) {
      // Make sure the entity has the field and the current user has
      // access to the field.
      if (!$entity->hasField($field_name) || !$entity->get($field_name)->access('view')) {
        continue;
      }

      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $entity->get($field_name);
      $field_type = $items->getFieldDefinition()->getType();
      if (!in_array($field_type, ['text_long', 'text_with_summary'])) {
        continue;
      }

      foreach ($items as $delta => $item) {
        $document = Html::load($item->value);
        $crawler = new Crawler($document);
        $indexes = [];
        $data = [];
        $crawler->filter('embedded-content')->each(function (Crawler $crawler) use ($field_name, $delta, $indexes, &$data): void {
          /** @var \DOMElement $node */
          $node = $crawler->getNode(0);

          $plugin_config = Json::decode($node->getAttribute('data-plugin-config'));
          $plugin_id = $node->getAttribute('data-plugin-id');

          /** @var \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentInterface $embedded_plugin */
          $embedded_plugin = $this->embeddedContentManager->createInstance(
            $plugin_id,
            $plugin_config
          );
          if ($embedded_plugin instanceof SchemaDotOrgEmbeddedContentInterface) {
            // @phpstan-ignore-next-line nullCoalesce.offset
            $indexes[$plugin_id] = $indexes[$plugin_id] ?? 0;
            $index = $indexes[$plugin_id];
            $data["$field_name-$delta-$plugin_id-$index"] = $embedded_plugin->getJsonId();
            $indexes[$plugin_id]++;
          }
        });
      }
    }
    return $data;
  }

}
