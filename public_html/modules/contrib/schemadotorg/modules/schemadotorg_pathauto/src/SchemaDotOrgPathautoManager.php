<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_pathauto;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\pathauto\AliasCleanerInterface;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Drupal\token\TokenInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Schema.org pathauto manager.
 */
class SchemaDotOrgPathautoManager implements SchemaDotOrgPathautoManagerInterface {
  use StringTranslationTrait;
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgPathautoManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\token\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\pathauto\AliasCleanerInterface $aliasCleaner
   *   The alias cleaner service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    #[Autowire(service: 'token')]
    protected TokenInterface $token,
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'pathauto.alias_cleaner')]
    protected AliasCleanerInterface $aliasCleaner,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    $patterns = $this->configFactory->get('schemadotorg_pathauto.settings')->get('patterns');
    $matched_patterns = $this->schemaTypeManager->getSetting($patterns, $mapping, ['multiple' => TRUE]);
    if (empty($matched_patterns)) {
      return;
    }

    // The first entry is the most specific match.
    // $pathauto_pattern holds the matched settings key (e.g. 'node--Thing').
    // $pathauto_settings holds the token pattern string (e.g. '[node:title]').
    $pathauto_pattern = array_key_first($matched_patterns);
    $pathauto_settings = $matched_patterns[$pathauto_pattern];

    // The entity type id and bundle come from the mapping (ground truth).
    // The remaining key segment (not entity_type_id, not bundle) is the Schema.org type.
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    $pathauto_pattern_key_parts = explode('--', $pathauto_pattern);
    $pattern_schema_type = NULL;
    foreach ($pathauto_pattern_key_parts as $part) {
      if ($part !== $entity_type_id && $part !== $bundle) {
        $pattern_schema_type = $part;
        break;
      }
    }

    // Define the pathauto pattern entity id and label.
    $entity_type_definition = $mapping->getTargetEntityTypeDefinition();
    $schema_type_definition = $this->schemaTypeManager->getType($pattern_schema_type ?? $mapping->getSchemaType());
    $pathauto_pattern_entity_id = 'schema_' . $entity_type_id . '_' . $schema_type_definition['drupal_name'];
    $pathauto_pattern_entity_label = 'Schema.org: ' . $entity_type_definition->getCollectionLabel() . ' - ' . $schema_type_definition['drupal_label'];

    // When the matched pattern is bundle-specific, include the bundle in the
    // pathauto pattern entity id and label so it does not conflict with the
    // generic schema type pattern.
    if (in_array($bundle, $pathauto_pattern_key_parts)) {
      $bundle_entity = $mapping->getTargetEntityBundleEntity();
      $bundle_label = $bundle_entity ? $bundle_entity->label() : $bundle;
      $pathauto_pattern_entity_id .= '_' . $bundle;
      $pathauto_pattern_entity_label .= ' (' . $bundle_label . ')';
    }

    // Load or create initial pathauto pattern entity with a selection condition.
    $pathauto_pattern_entity = PathautoPattern::load($pathauto_pattern_entity_id);
    if (!$pathauto_pattern_entity) {
      $pathauto_pattern_entity = PathautoPattern::create([
        'id' => $pathauto_pattern_entity_id,
        'label' => $pathauto_pattern_entity_label,
        'type' => 'canonical_entities:' . $entity_type_id,
        'pattern' => $pathauto_settings,
        'weight' => -10,
      ]);
      $pathauto_pattern_entity->addSelectionCondition([
        'id' => 'entity_bundle:' . $entity_type_id,
        'negate' => FALSE,
        'context_mapping' => [
          $entity_type_id => $entity_type_id,
        ],
      ]);
    }

    // Get the default selection condition.
    $selection_conditions_configuration = $pathauto_pattern_entity->getSelectionConditions()->getConfiguration();
    $selection_condition_id = array_key_first($selection_conditions_configuration);
    $selection_condition = $pathauto_pattern_entity->getSelectionConditions()->get($selection_condition_id);

    // Append the Schema.org mapping bundle to the selection condition.
    $configuration = $selection_condition->getConfiguration();
    $configuration['bundles'][$bundle] = $bundle;
    ksort($configuration['bundles']);
    $selection_condition->setConfiguration($configuration);

    $pathauto_pattern_entity->save();
  }

  /**
   * Alter the metadata about available placeholder tokens and token types.
   *
   * @param array $info
   *   The associative array of token definitions from hook_token_info().
   */
  public function tokenInfoAlter(array &$info): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface[] $mapping_types */
    $mapping_types = $this->getMappingTypeStorage()->loadMultiple();

    $entity_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($mapping_types as $mapping_type) {
      $entity_type_id = $mapping_type->get('target_entity_type_id');
      $entity_info = $entity_definitions[$entity_type_id] ?? NULL;
      if (!$entity_info || !$entity_info->get('token_type')) {
        continue;
      }

      $token_type = $entity_info->get('token_type');
      $info['tokens'][$token_type]['schemadotorg']['base-path'] = [
        'name' => $this->t('Schema.org type base path'),
        'description' => $this->t('The Schema.org type base path of the @entity.', ['@entity' => mb_strtolower((string) $entity_info->getLabel())]),
      ];
      $info['tokens'][$token_type]['schemadotorg']['alternate-name'] = [
        'name' => $this->t('Schema.org alternate name or entity label'),
        'description' => $this->t("The Schema.org alternate name or the @entity label. When applicable, an alternate name can be used to provide a short label/title for URL aliases.", ['@entity' => mb_strtolower((string) $entity_info->getLabel())]),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): ?array {
    $entity = $data[$type] ?? NULL;
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }

    $replacements = [];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'schemadotorg:base-path':
          $base_path = $this->getBasePath($entity);
          if ($base_path) {
            $replacements[$original] = $this->token->replace(
              $base_path,
              [$entity->getEntityTypeId() => $entity],
              ['callback' => [$this->aliasCleaner, 'cleanTokenValues']] + $options,
              $bubbleable_metadata
            );
          }
          break;

        case 'schemadotorg:alternate-name':
          $mapping = $this->getMappingStorage()->loadByEntity($entity);
          $alternate_field_name = ($mapping)
            ? $mapping->getSchemaPropertyFieldName('alternateName')
            : NULL;
          if ($alternate_field_name && $entity->hasField($alternate_field_name)) {
            $replacements[$original] = $entity->get($alternate_field_name)->value ?: $entity->label();
          }
          else {
            $replacements[$original] = $entity->label();
          }
          break;

      }
    }

    return $replacements;
  }

  /**
   * Get the base path for a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return string|null
   *   The base path for a content entity.
   */
  protected function getBasePath(ContentEntityInterface $entity): ?string {
    // Check that the content entity is mapped to a Schema.org type.
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if (!$mapping) {
      return NULL;
    }

    $base_paths = $this->configFactory->get('schemadotorg_pathauto.settings')->get('base_paths');
    $parts = [
      'entity_type_id' => $mapping->getTargetEntityTypeId(),
      'bundle' => $mapping->getTargetBundle(),
      'schema_type' => $mapping->getSchemaType(),
      'additional_type' => $this->getMappingStorage()->getAdditionalType($entity),
    ];
    return $this->schemaTypeManager->getSetting($base_paths, $parts);
  }

}
