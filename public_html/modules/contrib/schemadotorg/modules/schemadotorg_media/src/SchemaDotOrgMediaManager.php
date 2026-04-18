<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_media;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaSourceManager;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The Schema.org media manager.
 */
class SchemaDotOrgMediaManager implements SchemaDotOrgMediaManagerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgMediaManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\media\MediaSourceManager $mediaSourceManager
   *   The media source manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    #[Autowire(service: 'plugin.manager.media.source')]
    protected MediaSourceManager $mediaSourceManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingDefaultsAlter(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void {
    if ($entity_type_id !== 'media') {
      return;
    }

    // Add default source plugin id to entity type defaults.
    $defaults['entity']['source'] = $this->configFactory->get('schemadotorg_media.settings')
      ->get("default_sources.$schema_type") ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface &$form_state): void {
    if (!$this->moduleHandler->moduleExists('schemadotorg_ui')) {
      return;
    }

    /** @var \Drupal\schemadotorg\Form\SchemaDotOrgMappingForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null $mapping */
    $mapping = $form_object->getEntity();

    // Exit if no Schema.org type has been selected.
    if (!$mapping->getSchemaType()) {
      return;
    }

    // Make sure we are altering the 'Add Schema.org media type' form.
    if ($mapping->getTargetEntityTypeId() !== 'media' || !$mapping->isNew()) {
      return;
    }

    $mapping_defaults = $form_state->get('mapping_defaults');
    $source_default_value = $mapping_defaults['entity']['source'] ?? NULL;
    $source_field_mappings = $this->configFactory->get('schemadotorg_media.settings')
      ->get('source_field_mappings');

    // For new media types, hide all source field to Schema.org property mapping b
    // because they are dynamically set.
    // @see schemadotorg_media_media_type_insert
    if (empty($mapping_defaults['entity']['id'])) {
      foreach ($source_field_mappings as $schema_property) {
        $form['mapping']['properties'][$schema_property]['field'] = [
          '#markup' => $this->t('This property will automatically be mapped to the media source field, when applicable'),
        ];
      }
    }

    // Set subtype defaults from mapping defaults in $form_state.
    // @see \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm::buildFieldTypeForm
    $plugins = $this->mediaSourceManager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin_id => $definition) {
      $source_field_name = 'field_media_' . str_replace(':', '_', $plugin_id);
      $t_args = [
        '@label' => $definition['label'],
        '@field' => $source_field_name,
        '@property' => $source_field_mappings[$source_field_name] ?? '',
      ];
      $options[$plugin_id] = $this->t('@label (@field: @property)', $t_args);
    }
    $form['mapping']['entity']['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Media source'),
      '#default_value' => $source_default_value,
      '#options' => $options,
      '#description' => $this->t('Media source that is responsible for additional logic related to this media type.'),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function bundleEntityAlter(array &$values, string $schema_type, string $entity_type_id): void {
    if ($entity_type_id !== 'media_type') {
      return;
    }

    $entity_values =& $values['entity'];

    $source_plugin_id = $entity_values['source'];
    $source_field_name = 'field_media_' . str_replace(':', '_', $source_plugin_id);

    $entity_values['source_configuration'] = [];
    $entity_values['source_configuration']['source_field'] = $source_field_name;
    switch ($source_plugin_id) {
      // @see media.type.audio.yml
      // @see media.type.document.yml
      // @see media.type.image.yml
      // @see media.type.video.yml
      case 'audio_file':
      case 'file':
      case 'image':
      case 'video_file':
        $entity_values['field_map'] = ['name' => 'name'];
        break;

      // @see media.type.remote_video.yml
      case 'oembed:video':
        $entity_values['field_map'] = ['title' => 'name'];
        $entity_values['source_configuration']['thumbnails_directory'] = 'public://oembed_thumbnails/[date:custom:Y-m]';
        $entity_values['source_configuration']['providers'] = ['YouTube', 'Vimeo'];
        break;
    }

    // @see media.type.*.yml
    $entity_values['options'] = [
      'status' => TRUE,
      'new_revision' => FALSE,
      'queue_thumbnail_downloads' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function mediaTypeInsert(MediaTypeInterface $media_type): void {
    if ($media_type->isSyncing()) {
      return;
    }

    if (empty($media_type->schemaDotOrgType)) {
      return;
    }

    // If the media source is using a source field, ensure it's
    // properly created.
    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);

    if (!$source_field) {
      $source_field = $source->createSourceField($media_type);
      /** @var \Drupal\field\FieldStorageConfigInterface $storage */
      $storage = $source_field->getFieldStorageDefinition();
      if ($storage->isNew()) {
        $storage->save();
      }
      $source_field->save();
    }

    // Set the source field to Schema.org property mapping.
    $values =& $media_type->schemaDotOrgValues;
    $source_field_mappings = $this->configFactory->get('schemadotorg_media.settings')
      ->get('source_field_mappings');
    $source_field_name = $source_field->getName();
    $schema_property_name = $source_field_mappings[$source_field_name];
    $values['properties'][$schema_property_name]['name'] = $source_field_name;

    // Add the new field to the default form and view displays for this
    // media type.
    if ($source_field->isDisplayConfigurable('form')) {
      $display = $this->entityDisplayRepository->getFormDisplay('media', $media_type->id());
      $source->prepareFormDisplay($media_type, $display);
      $display->save();
    }
    if ($source_field->isDisplayConfigurable('view')) {
      $display = $this->entityDisplayRepository->getViewDisplay('media', $media_type->id());

      // Remove all default components.
      foreach (array_keys($display->getComponents()) as $name) {
        $display->removeComponent($name);
      }
      $source->prepareViewDisplay($media_type, $display);
      $display->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function propertyFieldAlter(
    string $schema_type,
    string $schema_property,
    array &$field_storage_values,
    array &$field_values,
    ?string &$widget_id,
    array &$widget_settings,
    ?string &$formatter_id,
    array &$formatter_settings,
  ): void {
    $target_type = NestedArray::getValue($field_storage_values, ['settings', 'target_type']);
    if (!in_array($field_storage_values['type'], ['entity_reference', 'entity_reference_revisions'])
      || $target_type !== 'media') {
      return;
    }

    // If the Media library is installed use it via the widget's form display.
    if (empty($widget_id) && $this->moduleHandler->moduleExists('media_library')) {
      $widget_id = 'media_library_widget';
    }

    // Display the rendered media.
    if (empty($formatter_id)) {
      $formatter_id = 'entity_reference_entity_view';
    }
  }

}
