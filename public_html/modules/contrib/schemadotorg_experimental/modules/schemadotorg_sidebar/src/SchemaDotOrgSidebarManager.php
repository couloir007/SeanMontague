<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_sidebar;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org sidebar manager.
 */
class SchemaDotOrgSidebarManager implements SchemaDotOrgSidebarManagerInterface {

  /**
   * Constructs a SchemaDotOrgSidebarManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    // Make sure we are adding sidebars to nodes.
    $entity_type = $mapping->getTargetEntityTypeId();
    if ($entity_type !== 'node') {
      return;
    }

    $paragraph_types = $this->configFactory->get('schemadotorg_sidebar.settings')->get('paragraph_types');
    foreach ($paragraph_types as $paragraph_type_id => $settings) {
      if (isset($settings['schema_types']) && $this->schemaTypeManager->getSetting($settings['schema_types'], $mapping)) {
        $this->insertSidebar($mapping, $paragraph_type_id, $settings);
      }
    }
  }

  /**
   * Insert sidebar field group when a mapping is saved.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   * @param string $paragraph_type_id
   *   The machine name of the paragraph type for which the sidebar will be created.
   * @param array $settings
   *   The paragraph type settings.
   */
  protected function insertSidebar(SchemaDotOrgMappingInterface $mapping, string $paragraph_type_id, array $settings): void {
    $paragraph_type = ParagraphsType::load($paragraph_type_id);
    if (!$paragraph_type) {
      return;
    }

    $settings += [
      'form_format_type' => 'details_sidebar',
      'form_format_settings' => [],
      'view_format_type' => 'fieldset',
      'view_format_settings' => [],
    ];

    $entity_type = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    $field_name = 'field_' . $paragraph_type_id;
    $field_label = $paragraph_type->label();

    $group_name = 'group_' . $paragraph_type_id;
    $group_label = $paragraph_type->label();
    $group_description = $paragraph_type->getDescription();

    // Create the field storage.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference_revisions',
        'cardinality' => 1,
        'settings' => [
          'target_type' => 'paragraph',
        ],
      ]);
      $field_storage->save();
    }

    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    // If the field's config exists, do not create the field, and update its
    // form and view display.
    $field_config = $field_config_storage->load("node.$bundle.$field_name");
    if ($field_config) {
      return;
    }

    // Create the field instance.
    $field_config_storage->create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $field_label,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'target_bundles' => [$paragraph_type_id => $paragraph_type_id],
          'negate' => 0,
          'target_bundles_drag_drop' => [
            $paragraph_type_id => ['weight' => 0, 'enabled' => TRUE],
          ],
        ],
      ],
    ])->save();

    // Create the form display component.
    $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type, $bundle);
    $form_display->setComponent($field_name, [
      'type' => 'inline_entity_form_simple',
    ]);
    $form_display->setThirdPartySetting('field_group', $group_name, [
      'label' => $group_label,
      'children' => [$field_name],
      'parent_name' => '',
      // After all other sidebars.
      'weight' => 230,
      'format_type' => $settings['form_format_type'],
      'format_settings' => $settings['form_format_settings']
        + ['description' => $group_description],
      'region' => 'content',
    ]);
    $form_display->save();

    // Create the view display component.
    $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type, $bundle);
    if ($settings['view_format_type'] === 'hidden') {
      $view_display->removeComponent($field_name);
    }
    else {
      $view_display->setComponent($field_name, [
        'type' => 'entity_reference_revisions_entity_view',
        'label' => 'hidden',
      ]);
      $view_display->setThirdPartySetting('field_group', $group_name, [
        'label' => $group_label,
        'children' => [$field_name],
        'parent_name' => '',
        // Before links.
        'weight' => 99,
        'format_type' => $settings['view_format_type'],
        'format_settings' => $settings['view_format_settings'],
        'region' => 'content',
      ]);
    }
    $view_display->save();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldWidgetSingleElementInlineEntityFormSimpleFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    // Remove the nested fieldset from the inline entity form.
    /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $items */
    $items = $context['items'];
    $field_name = $items->getName();
    $paragraph_type_id = preg_replace('/^field_/', '', $field_name);
    $settings = $this->configFactory->get('schemadotorg_sidebar.settings')->get("paragraph_types.$paragraph_type_id");
    if ($settings !== NULL) {
      $element['#theme_wrappers'] = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function nodeViewAlter(array &$build, NodeInterface $node, EntityViewDisplayInterface $display): void {
    $paragraph_types = $this->configFactory->get('schemadotorg_sidebar.settings')->get('paragraph_types');
    foreach (array_keys($paragraph_types) as $paragraph_type_id) {
      $field_name = 'field_' . $paragraph_type_id;
      if (empty($build[$field_name])
        || empty($build[$field_name][0])) {
        continue;
      }

      // Render the editorial paragraph and determined if it has any content.
      $content = $build[$field_name][0];
      $output = (string) DeprecationHelper::backwardsCompatibleCall(
        currentVersion: \Drupal::VERSION,
        deprecatedVersion: '10.3',
        currentCallable: fn() => $this->renderer->renderInIsolation($content),
        deprecatedCallable: fn() => $this->renderer->renderPlain($content),
      );
      if (trim(strip_tags($output)) === '') {
        $build[$field_name]['#access'] = FALSE;
      }
    }
  }

}
