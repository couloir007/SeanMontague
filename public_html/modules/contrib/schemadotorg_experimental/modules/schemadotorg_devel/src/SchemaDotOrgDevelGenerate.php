<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_devel;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Drupal\style_options\StyleOptionConfigurationDiscovery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The Schema.org devel generate service.
 */
class SchemaDotOrgDevelGenerate implements SchemaDotOrgDevelGenerateInterface {
  use StringTranslationTrait;
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Default filter format.
   */
  protected string $defaultFormat;

  /**
   * Cached array of style options defaults.
   */
  protected array $styleOptionDefaults;

  /**
   * Array of entity IDs currently being processed to prevent circular references.
   */
  protected array $processingEntities = [];

  /**
   * Constructs a SchemaDotOrgDevelGenerate object.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
    protected Connection $database,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected ?StyleOptionConfigurationDiscovery $styleOptionDiscovery,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function menuLocalTasksAlter(array &$data, string $route_name, RefinableCacheableDependencyInterface $cacheability): void {
    if ($route_name !== 'node.add') {
      return;
    }

    $cacheability->addCacheContexts(['url.query_args', 'user.roles']);

    // Check for node generate tab.
    $tabs =& NestedArray::getValue($data, ['tabs', 0]);
    if (!$tabs || !isset($tabs['schemadotorg_devel.node.generate'])) {
      return;
    }

    // Remove tabs if the user can't generate content.
    if (!$this->hasDevelGeneratePermission()) {
      unset(
        $data['tabs'][0]['schemadotorg_devel.node.add'],
        $data['tabs'][0]['schemadotorg_devel.node.generate'],
      );
      return;
    }

    // Append 'schemadotorg_devel_generate' query parameter to the
    // 'Generate content' task.
    /** @var \Drupal\Core\Url $url */
    $url = $tabs['schemadotorg_devel.node.generate']['#link']['url'];
    $query = $this->request()->query->all();
    $url->setOption('query', $query + ['schemadotorg_devel_generate' => 'test']);

    // Remove 'schemadotorg_devel_generate' query parameter from the
    // 'Generate content' task.
    /** @var \Drupal\Core\Url $url */
    $url = $data['tabs'][0]['schemadotorg_devel.node.add']['#link']['url'];
    $query = $this->request()->query->all();
    unset($query['schemadotorg_devel_generate']);
    $url->setOption('query', $query);

    // Set the active task.
    if ($this->isDevelGenerateRequest()) {
      $tabs['schemadotorg_devel.node.add']['#active'] = FALSE;
      $tabs['schemadotorg_devel.node.generate']['#active'] = TRUE;
    }
    else {
      $tabs['schemadotorg_devel.node.add']['#active'] = TRUE;
      $tabs['schemadotorg_devel.node.generate']['#active'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function nodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!$this->hasDevelGeneratePermission()) {
      return;
    }

    if ($this->isDevelGenerateRequest()
      && $this->request()->isMethod('GET')) {
      $element =& NestedArray::getValue($form, ['moderation_state', 'widget', 0]);
      if ($element) {
        $element['#after_build'][] = [
          get_class($this),
          'moderationStateAfterBuild',
        ];
      }
    }

    // Alter the Mercury Editor form for new nodes.
    if (str_ends_with($form_id, 'mercury_editor_form')) {
      /** @var \Drupal\mercury_editor\Entity\MercuryEditorNodeForm $form_object */
      $form_object = $form_state->getFormObject();
      /** @var \Drupal\node\NodeInterface $node */
      $node = $form_object->getEntity();
      if ($node->isNew()) {
        $url = Url::fromRoute('node.add', ['node_type' => $node->bundle()]);
        $query = $this->request()->query->all();
        if ($this->isDevelGenerateRequest()) {
          unset($query['schemadotorg_devel_generate']);
          $title = $this->t('Add content');
        }
        else {
          $query += ['schemadotorg_devel_generate' => 'test'];
          $title = $this->t('Generate content');
        }

        $form['schemadotorg_devel_generate'] = [
          '#type' => 'link',
          '#title' => $title,
          '#url' => $url->setOption('query', $query),
          '#attributes' => [
            'class' => ['schemadotorg-devel-generate-button', 'button', 'button--small', 'button--extrasmall'],
          ],
          '#weight' => -100,
        ];
      }
    }
  }

  /**
   * Form element #after_build callback: Change the moderation state to published.
   *
   * Must override the moderation state widget #value after it is built because the
   * default value (and value) is set via the ModerationStateWidget.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered form element.
   *
   * @see \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget::formElement
   */
  public static function moderationStateAfterBuild(array $element, FormStateInterface $form_state): array {
    $element['state']['#default_value'] = 'published';
    $element['state']['#value'] = 'published';
    return $element;
  }

  /* *********************************************************************** */
  // Entity hooks.
  /* *********************************************************************** */

  /**
   * {@inheritdoc}
   */
  public function nodeCreate(NodeInterface $node): void {
    if (!$this->hasDevelGeneratePermission()
      || !$this->isDevelGenerateRequest()
      || !$this->isGet()) {
      return;
    }

    $this->generateSampleFieldValues($node);

    $this->alterSampleFieldValues($node);

    $this->trackOrphanedParagraphs($node);

    // Remove menu link which is not useful for generated content.
    unset($node->menu_link);
  }

  /**
   * {@inheritdoc}
   */
  public function nodePresave(NodeInterface $node): void {
    if (!$this->isDevelGenerateRequest()
      || !empty($node->devel_generate)) {
      return;
    }

    // Issue #3373368: Media Library edge case: selecting already existing
    // media programmatically.
    // @see https://www.drupal.org/project/drupal/issues/3373368
    //
    // The below code works around programmatic media entity references not
    // working as expected when using the media library.
    $field_definitions = $this->getFieldDefinitions($node);
    foreach ($field_definitions as $field_definition) {
      $field_name = $field_definition->getName();
      if ($node->get($field_name)->isEmpty()
        && str_starts_with($field_definition->getType(), 'entity_reference')) {
        $settings = $field_definition->getSettings();
        $target_type = NestedArray::getValue($settings, ['handler_settings', 'target_type']);
        if ($target_type === 'media') {
          $node->get($field_name)->generateSampleItems();
          /** @var \Drupal\media\MediaInterface $entity */
          $entity = $node->get($field_name)->entity;
          // Never create a new entities.
          if ($entity->isNew()) {
            $node->get($field_name)->setValue([]);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityPresave(EntityInterface $entity): void {
    // Check that devel is generating a content entity.
    // @see https://www.drupal.org/project/devel/issues/2582845
    if (empty($entity->devel_generate)
      || !$entity instanceof ContentEntityInterface) {
      return;
    }

    $this->alterSampleFieldValues($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessNode(array &$variables): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];

    // Make sure new node's links are in preview to prevent the below error
    // when generating content.
    //
    // Error: Call to a member function getTranslation() on null in
    // Drupal\node\NodeViewBuilder::renderLinks()
    // (line 100 of core/modules/node/src/NodeViewBuilder.php).
    $is_in_preview =& NestedArray::getValue($variables, ['content', 'links', '#lazy_builder', 1, 3]);
    if ($node->isNew() && $is_in_preview === FALSE) {
      $is_in_preview = TRUE;
    }
  }

  /* *********************************************************************** */
  // Track generated (and saved) paragraphs.
  //
  // We are tracking paragraphs because when we call
  // EntityReferenceRevisionsItem::generateSampleValue is saves the entity.
  // If the generated node/entity is not saved, the paragraph is remains in
  // the database as orphaned. This is a HUGE problem because the database
  // can fill up with empty paragraphs.
  //
  // @see https://www.drupal.org/project/entity_reference_revisions/issues/3394509
  /* *********************************************************************** */

  /**
   * {@inheritdoc}
   */
  public function deleteOrphanedParagraphs(): void {
    // Get revision ids (max 20) for paragraph generated but not saved
    // more than an hour agp.
    $revision_ids = $this->database
      ->select('schemadotorg_devel_generate_paragraphs', 'p')
      ->fields('p', ['revision_id'])
      ->condition('er.created', strtotime("-1 hour"), '<')
      ->orderBy('p.created')
      ->range(0, 100)
      ->execute()
      ->fetchCol();
    if (!$revision_ids) {
      return;
    }

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $paragraph_storage */
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
    $paragraphs = $paragraph_storage->loadMultipleRevisions($revision_ids);
    foreach ($paragraphs as $paragraph) {
      $has_parent = FALSE;
      /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
      while ($parent = $paragraph->getParentEntity()) {
        if ($parent->getEntityTypeId() === 'node') {
          $has_parent = TRUE;
          break;
        }
      }

      if (!$has_parent) {
        $paragraph->delete();
      }
    }

    // Delete records.
    $this->database
      ->delete('schemadotorg_devel_generate_paragraphs')
      ->condition('revision_id', $revision_ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function trackOrphanedParagraphs(ContentEntityInterface $entity): void {
    $field_definitions = $this->getFieldDefinitions($entity);
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() !== 'entity_reference_revisions'
        || $field_definition->getSetting('target_type') !== 'paragraph') {
        continue;
      }

      foreach ($entity->get($field_name) as $item) {
        $this->database
          ->insert('schemadotorg_devel_generate_paragraphs')
          ->fields([
            'revision_id' => $item->target_revision_id,
            'created' => time(),
          ])
          ->execute();
        $this->trackOrphanedParagraphs($item->entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasDevelGeneratePermission(): bool {
    return $this->currentUser->hasPermission('generate schemadotorg content');
  }

  /**
   * {@inheritdoc}
   */
  public function isDevelGenerateRequest(): bool {
    return (bool) $this->request()->query->get('schemadotorg_devel_generate');
  }

  /**
   * {@inheritdoc}
   */
  public function isGet(): bool {
    return $this->request()->isMethod('GET');
  }

  /* *********************************************************************** */
  // Generate sample field value methods.
  /* *********************************************************************** */

  /**
   * Generates sample field values for a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity.
   */
  protected function generateSampleFieldValues(ContentEntityInterface $entity): void {
    $field_definitions = $this->getFieldDefinitions($entity);
    foreach ($field_definitions as $field_definition) {
      $field_name = $field_definition->getName();
      if (!$entity->get($field_name)->isEmpty()) {
        continue;
      }

      if ($this->generateSampleFieldValuesBySettings($entity, $field_definition)) {
        continue;
      }

      if ($field_definition->getType() === 'entity_reference_revisions'
        && $field_definition->getSetting('target_type') === 'paragraph'
        && $field_definition instanceof FieldConfigInterface) {
        $this->generateSampleParagraphs($entity, $field_definition);
      }
      else {
        $this->generateSampleItems($entity, $field_definition);
      }
    }
  }

  /**
   * Generate sample field values on an entity based on predefined settings.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   TRUE if the field values were successfully assigned, FALSE otherwise.
   */
  protected function generateSampleFieldValuesBySettings(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition): bool {
    $mapping = SchemaDotOrgMapping::loadByEntity($entity);
    $field_name = $field_definition->getName();

    $property_values = $this->configFactory
      ->get('schemadotorg_devel.settings')
      ->get('generate_values');

    $parts = [
      'entity_type_id' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'field_name' => $field_name,
      'schema_type' => $mapping?->getSchemaType(),
      'schema_property' => $mapping?->getSchemaPropertyMapping($field_name, TRUE),
    ];

    $property_value = $this->schemaTypeManager->getSetting($property_values, $parts);
    if (is_null($property_value)) {
      return FALSE;
    }
    elseif (empty($property_value)) {
      $entity->get($field_name)->setValue([]);
      return TRUE;
    }

    $random = new Random();
    $main_property = $field_definition
      ->getFieldStorageDefinition()
      ->getMainPropertyName();
    if (isset($property_value['values'])) {
      foreach ($entity->get($field_name) as $item) {
        $value = $property_value['values'][array_rand($property_value['values'])];
        $item->set($main_property, $value);
        return TRUE;
      }
    }
    else {
      foreach ($property_value as $method => $parameter) {
        foreach ($entity->get($field_name) as $item) {
          switch ($method) {
            case 'words':
              $value = ucfirst(strtolower($random->sentences($parameter, TRUE)));
              break;

            default:
              $value = $random->$method($parameter);
              break;
          }
          $item->set($main_property, $value);
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Generate sample paragraphs for a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Field\FieldConfigInterface $field
   *   The field configuration.
   */
  protected function generateSampleParagraphs(ContentEntityInterface $entity, FieldConfigInterface $field): void {
    $field_name = $field->getName();

    // Get sorted and enabled target bundles without 'From library'.
    $settings = $field->getSettings();
    $target_bundles_drag_drop = $settings['handler_settings']['target_bundles_drag_drop'] ?? NULL;
    if ($target_bundles_drag_drop) {
      uasort($target_bundles_drag_drop, [SortArray::class, 'sortByWeightElement']);
      $target_bundles_drag_drop = array_filter($target_bundles_drag_drop, fn($value) => !empty($value['enabled']));
      $target_bundles = array_keys($target_bundles_drag_drop);
      $target_bundles = array_combine($target_bundles, $target_bundles);
    }
    else {
      $target_bundles = $settings['handler_settings']['target_bundles'];
    }
    unset($target_bundles['from_library']);

    $cardinality = $field->getFieldStorageDefinition()->getCardinality();
    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $values = [];
      foreach ($target_bundles as $target_bundle) {
        // Skip layout paragraphs.
        if ($this->hasLayoutParagraphs()) {
          /** @var \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type */
          $paragraph_type = $this->entityTypeManager
            ->getStorage('paragraphs_type')
            ->load($target_bundle);
          $layout_paragraphs_behavior = $paragraph_type->getBehaviorPlugin('layout_paragraphs');
          $configuration = $layout_paragraphs_behavior->getConfiguration();
          if (!empty($configuration['available_layouts'])) {
            continue;
          }
        }

        $temp_field_definition = clone $field;
        $handler_settings = $field->getSetting('handler_settings');
        $handler_settings['target_bundles'] = [$target_bundle => $target_bundle];
        $temp_field_definition->setSetting('handler_settings', $handler_settings);
        $values[] = EntityReferenceRevisionsItem::generateSampleValue($temp_field_definition);
      }
      $entity->get($field_name)->setValue($values);
    }
    else {
      $temp_field_definition = clone $field;
      $handler_settings = $field->getSetting('handler_settings');
      $handler_settings['target_bundles'] = $target_bundles;
      $temp_field_definition->setSetting('handler_settings', $handler_settings);

      $values = [];
      for ($i = 0; $i < $cardinality; $i++) {
        $values[] = EntityReferenceRevisionsItem::generateSampleValue($temp_field_definition);
      }
      $entity->get($field_name)->setValue($values);
    }

    // Set behavior settings.
    foreach ($entity->get($field_name) as $item) {
      /** @var \Drupal\paragraphs\ParagraphInterface $target_entity */
      $target_entity = $item->entity;

      // Reset behavior settings.
      $target_entity->setAllBehaviorSettings([]);

      // Define layout paragraph defaults.
      if ($this->hasLayoutParagraphs()) {
        $target_entity->setBehaviorSettings('layout_paragraphs', [
          'parent_uuid' => NULL,
          'region' => NULL,
        ]);
      }

      // Define style options behavior defaults.
      if ($this->moduleHandler->moduleExists('style_options')) {
        $style_options_defaults = $this->getBundleStyleOptionDefaults($target_entity->bundle());
        if ($style_options_defaults) {
          $target_entity->setBehaviorSettings('style_options', $style_options_defaults);
        }
      }

      $target_entity->save();
    }

    // Alter all generated paragraphs.
    foreach ($entity->get($field_name) as $item) {
      /** @var \Drupal\paragraphs\ParagraphInterface $target_entity */
      $target_entity = $item->entity;
      $this->alterSampleFieldValues($target_entity);
      $target_entity->save();
    }
  }

  /**
   * Get the default style option for a given bundle.
   *
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   The default style options for the bundle.
   */
  protected function getBundleStyleOptionDefaults(string $bundle): array {
    if (!isset($this->styleOptionDefaults) && $this->styleOptionDiscovery) {
      $this->styleOptionDefaults = [];
      // Make sure there are style option definitions.
      if ($this->styleOptionDiscovery->getDefinitions()) {
        $option_definitions = $this->styleOptionDiscovery->getOptionDefinitions();
        foreach ($option_definitions as $option_id => $option_definition) {
          if (array_key_exists('default', $option_definition)) {
            $this->styleOptionDefaults[$option_id] = $option_definition['default'];
          }
        }
      }
    }

    // Make sure there are style option defaults.
    if (empty($this->styleOptionDefaults)) {
      return [];
    }

    $context_options = $this->styleOptionDiscovery->getContextOptions('paragraphs', $bundle);
    return array_intersect_key($this->styleOptionDefaults, array_filter($context_options));
  }

  /**
   * Generate sample items for a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   */
  protected function generateSampleItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition): void {
    $field_name = $field_definition->getName();
    $field_type = $field_definition->getType();

    $field_storage = $field_definition->getFieldStorageDefinition();
    $max = $field_storage->getCardinality();
    if ($max === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $max = 3;
    }

    $entity->get($field_name)->generateSampleItems($max);

    if (str_starts_with($field_type, 'entity_reference')) {
      $items = $entity->get($field_name);
      foreach ($items as $item) {
        /** @var \Drupal\Core\Entity\EntityInterface|null $target_entity */
        $target_entity = $item->entity;
        if (!$target_entity instanceof ContentEntityInterface) {
          continue;
        }

        // Never create new entities.
        if ($target_entity->isNew()) {
          $entity->get($field_name)->setValue([]);
          break;
        }

        $this->alterSampleFieldValues($target_entity);
      }
    }
  }

  /* *********************************************************************** */
  // Alter sample field value methods.
  /* *********************************************************************** */

  /**
   * Alters the sample values of a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity whose sample values will be altered.
   */
  protected function alterSampleFieldValues(ContentEntityInterface $entity): void {
    // Prevent circular references by tracking entities currently being processed.
    $entity_key = $entity->getEntityTypeId() . ':' . $entity->id();
    if (in_array($entity_key, $this->processingEntities)) {
      return;
    }

    $this->processingEntities[] = $entity_key;

    try {
      $this->alterFieldValuesByType($entity);
      $this->alterFieldValuesBySettings($entity);
      $this->alterEntityLabel($entity);
    }
    finally {
      // Remove from processing list when done.
      $this->processingEntities = array_diff($this->processingEntities, [$entity_key]);
    }
  }

  /**
   * Alter generated field values based on a field types for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity.
   */
  protected function alterFieldValuesByType(ContentEntityInterface $entity): void {
    $random = new Random();
    $field_definitions = $this->getFieldDefinitions($entity);
    foreach ($field_definitions as $field_name => $field_definition) {
      // Generate sample value by field type.
      switch ($field_definition->getType()) {
        case 'entity_reference_override':
          // Limit entity reference override to 50 characters.
          foreach ($entity->get($field_name) as &$item) {
            $item->override = $random->word(mt_rand(1, 50));
          }
          break;

        case 'custom':
          // Limit custom field properties.
          $property_words = [
            'name' => 3,
            'text' => 10,
            'description' => 10,
          ];
          foreach ($entity->get($field_name) as &$item) {
            $item_value = $item->getValue();
            foreach ($item_value as $property_name => $property_value) {
              if (isset($property_words[$property_name])) {
                $item_value[$property_name] = $random->sentences($property_words[$property_name]);
              }
            }
            $item->setValue($item_value);
          }
          break;

        case 'entity_reference_revisions':
          // Remove all reference to paragraph library item.
          /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $items */
          $items = $entity->get($field_name);
          $indexes = [];
          foreach ($items as $index => $item) {
            if ($item->entity && $item->entity->bundle() === 'from_library') {
              $indexes[] = $index;
            }
          }
          if ($indexes) {
            $values = $items->getValue();
            foreach ($indexes as $index) {
              unset($values[$index]);
            }
            $items->setValue(array_values($values));
          }
          break;

        case 'text_long':
        case 'text_with_summary':
          foreach ($entity->get($field_name) as $item) {
            // Limit text with summary to a single summary paragraph with three paragraphs.
            if ($field_definition->getType() === 'text_with_summary') {
              $item->summary = $random->paragraphs(1);
            }
            // Limit text to three paragraphs.
            $item->value = _filter_autop($random->paragraphs(3));
            $item->format = $this->getFieldDefinitionAllowedFormat($field_definition);
          }
          break;

        case 'link':
          foreach ($entity->get($field_name) as $item) {
            $item->title = ucfirst(strtolower($random->sentences(5, TRUE)));
          }
          break;

        case 'string_long':
          // Limit long string to one paragraph.
          foreach ($entity->get($field_name) as $item) {
            $item->value = $random->paragraphs(1);
          }
          break;
      }
    }
  }

  /**
   * Alter generated field values based on a Schema.org devel generate settings for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity.
   */
  protected function alterFieldValuesBySettings(ContentEntityInterface $entity): void {
    $field_definitions = $this->getFieldDefinitions($entity);
    foreach ($field_definitions as $field_definition) {
      $this->generateSampleFieldValuesBySettings($entity, $field_definition);
    }
  }

  /**
   * Prefix a generated entity's label with the entity's bundle's label.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity.
   */
  protected function alterEntityLabel(ContentEntityInterface $entity): void {
    $bundle_entity_type_id = $entity->getEntityType()->getBundleEntityType();
    if (!$bundle_entity_type_id) {
      return;
    }

    $field_names = [
      'title',
    ];
    // Append Schema.org title, name, and description properties to field names.
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if ($mapping) {
      $schema_properties = [
        'name',
        'headline',
        'description',
        'text',
      ];
      foreach ($schema_properties as $schema_property) {
        $field_name = $mapping->getSchemaPropertyFieldName($schema_property);
        if ($field_name) {
          $field_names[] = $field_name;
        }
      }
    }
    foreach ($field_names as $field_name) {
      if ($entity->hasField($field_name)) {
        $bundle_entity_type = $this->entityTypeManager
          ->getStorage($bundle_entity_type_id)
          ->load($entity->bundle());
        $entity->set($field_name, $bundle_entity_type->label()
          . ' - '
          . $entity->get($field_name)->value);
        break;
      }
    }
  }

  /* *********************************************************************** */
  // Helper methods.
  /* *********************************************************************** */

  /**
   * Retrieves the currently active request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The currently active request object.
   */
  protected function request(): ?Request {
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * Gets the field definitions for a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of field definitions for the bundle, keyed by field name.
   */
  protected function getFieldDefinitions(ContentEntityInterface $entity): array {
    return $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * Get the allowed format for a given field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return string
   *   The allowed format.
   */
  protected function getFieldDefinitionAllowedFormat(FieldDefinitionInterface $field_definition): string {
    if (!isset($this->defaultFormat)) {
      $this->defaultFormat = $this->entityTypeManager
        ->getStorage('filter_format')
        ->load('full_html')
        ? 'full_html'
        : filter_default_format();
    }

    $allowed_formats = $field_definition->getSetting('allowed_formats');
    return ($allowed_formats) ? reset($allowed_formats) : $this->defaultFormat;
  }

  /**
   * Determine if the layout paragraphs module is enabled.
   *
   * @return bool
   *   TRUE if the layout paragraphs module is enabled.
   */
  protected function hasLayoutParagraphs(): bool {
    return $this->moduleHandler->moduleExists('layout_paragraphs');
  }

}
