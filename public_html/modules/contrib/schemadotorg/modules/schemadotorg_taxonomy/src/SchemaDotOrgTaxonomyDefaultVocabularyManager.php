<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_taxonomy;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org taxonomy vocabulary property manager.
 */
class SchemaDotOrgTaxonomyDefaultVocabularyManager implements SchemaDotOrgTaxonomyDefaultVocabularyManagerInterface {
  use StringTranslationTrait;
  use SchemaDotOrgTaxonomyTrait;

  /**
   * Constructs a SchemaDotOrgTaxonomyDefaultVocabularyManager object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface $schemaEntityTypeBuilder
   *   The Schema.org entity type builder.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface|null $contentTranslationManager
   *   The content translation manager.
   */
  public function __construct(
    protected MessengerInterface $messenger,
    protected LoggerChannelFactoryInterface $logger,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgEntityTypeBuilderInterface $schemaEntityTypeBuilder,
    protected ?ContentTranslationManagerInterface $contentTranslationManager = NULL,
  ) {}

  /**
   * Add default vocabulary to content types when a mapping is inserted.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    // Make sure we are adding default vocabularies to nodes.
    if ($entity_type_id !== 'node') {
      return;
    }

    $default_vocabularies = $this->configFactory->get('schemadotorg_taxonomy.settings')
      ->get('default_vocabularies');
    foreach ($default_vocabularies as $field_name => $vocabulary_settings) {
      $schema_types = $vocabulary_settings['schema_types'] ?? NULL;
      // Check if the default vocabulary is for a specific Schema.org type.
      if ($schema_types
        && !$this->schemaTypeManager->getSetting($schema_types, $mapping, ['negate' => TRUE])) {
        continue;
      }

      $default_field_prefix = $this->configFactory->get('field_ui.settings')->get('field_prefix') ?? 'field_';

      // Create vocabulary.
      $vocabulary_id = $vocabulary_settings['id'] ?? $field_name;
      $vocabulary = $this->createVocabulary($vocabulary_id, $vocabulary_settings);

      $field = $vocabulary_settings + [
        // Default field settings.
        'type' => 'field_ui:entity_reference:taxonomy_term',
        'label' => $vocabulary->label(),
        'unlimited' => TRUE,
        // Entity type and bundle.
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
        'field_name' => $default_field_prefix . $field_name,
        // Schema.org type and property.
        'schema_type' => $mapping->getSchemaType(),
        'schema_property' => '',
        // Additional defaults.
        'group' => $vocabulary_settings['group'] ?? NULL,
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [$vocabulary_id => $vocabulary_id],
          'auto_create' => $vocabulary_settings['auto_create'] ?? FALSE,
        ],
      ];

      $field_config_id = "$entity_type_id.$bundle.$default_field_prefix$field_name";
      $field_config = $this->entityTypeManager
        ->getStorage('field_config')
        ->load($field_config_id);
      if (!$field_config) {
        $this->schemaEntityTypeBuilder->addFieldToEntity(
          $entity_type_id,
          $bundle,
          $field
        );
      }
    }
  }

}
