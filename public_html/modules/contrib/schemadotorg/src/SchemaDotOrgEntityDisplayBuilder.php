<?php

declare(strict_types=1);

namespace Drupal\schemadotorg;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Schema.org entity display builder.
 *
 * The Schema.org entity display builder service sets a Schema.org property's
 * field's entity display component settings ana weight.
 */
class SchemaDotOrgEntityDisplayBuilder implements SchemaDotOrgEntityDisplayBuilderInterface {
  use StringTranslationTrait;
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgEntityDisplayBuilder object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
   *   The redirect destination service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    protected RequestStack $requestStack,
    protected RedirectDestinationInterface $redirectDestination,
    protected MessengerInterface $messenger,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldWeights(): array {
    $weights = $this->configFactory
      ->get('schemadotorg.settings')
      ->get('schema_properties.default_field_weights');
    $weights = array_flip($weights);

    // Limit default field weight to maximum of 200 while preserving the weight
    // of first 1/2 of the weights (i.e., 100).
    $max_weight = 200;
    $mid_weight = $max_weight / 2;
    if (count($weights) < $max_weight) {
      foreach ($weights as $key => $value) {
        $weights[$key] = $value + 1;
      }
    }
    else {
      $increment = $mid_weight / (count($weights) - $mid_weight);
      $weight = $mid_weight;
      foreach ($weights as $key => $value) {
        if ($value < $mid_weight) {
          $weights[$key] = $value + 1;
        }
        else {
          $weights[$key] = (int) round($weight) + 1;
          $weight += $increment;
        }
      }
    }
    return $weights;
  }

  /**
   * Get the default field weight for Schema.org property.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   THe field name.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return int
   *   The default field weight for Schema.org property.
   */
  public function getSchemaPropertyDefaultFieldWeight(string $entity_type_id, string $bundle, string $field_name, string $schema_type, string $schema_property): int {
    // Get the main Schema.org property.
    // (i.e., 'name' is the main property for 'name:prefix'.)
    $schema_property = explode(':', $schema_property)[0];

    // Check default field weights.
    $default_field_weights = $this->getDefaultFieldWeights();
    $parts = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => $field_name,
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
    ];
    $default_field_weight = $this->schemaTypeManager->getSetting($default_field_weights, $parts, ['parents' => FALSE]);
    if (!is_null($default_field_weight)) {
      return $default_field_weight;
    }

    // Determine max field weight rounded up to 10.
    $max_field_weight = ($default_field_weights)
      ? (int) ceil(max($default_field_weights) / 10) * 10
      : 0;
    if ($max_field_weight > 200) {
      $max_field_weight = 200;
    }

    // Get the field storage entity.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->load("$entity_type_id.$field_name");
    if (!$field_storage) {
      return $max_field_weight;
    }

    // Determine the field weight by the field type.
    $field_type_weights = [
      // Text fields.
      'string' => 10 + $max_field_weight,
      'integer' => 10 + $max_field_weight,
      'float'  => 10 + $max_field_weight,
      'decimal' => 10 + $max_field_weight,
      'datetime' => 11 + $max_field_weight,
      'duration' => 12 + $max_field_weight,
      // Text areas.
      'string_long' => 20 + $max_field_weight,
      'text_long' => 20 + $max_field_weight,
      'text_with_summary' => 20 + $max_field_weight,
      // Options.
      'list_string' => 30 + $max_field_weight,
      'list_integer' => 30 + $max_field_weight,
      'list_float' => 30 + $max_field_weight,
      'list_decimal' => 30 + $max_field_weight,
      'boolean' => 31 + $max_field_weight,
      // File and Links.
      'file' => 40 + $max_field_weight,
      'link' => 41 + $max_field_weight,
      'custom_field' => 42 + $max_field_weight,
      // Custom (50 - 55).
      // Entity references.
      'entity_reference' => 60 + $max_field_weight,
      'entity_reference_revisions' => 61 + $max_field_weight,
      'entity_reference_entity_modify' => 62 + $max_field_weight,
    ];
    $field_weight = $field_type_weights[$field_storage->getType()]
      ?? 50 + $max_field_weight;

    // Add 5 to weight for multiple value fields.
    if ($field_storage->getCardinality() !== 1) {
      $field_weight += 5;
    }

    return $field_weight;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeDisplays(SchemaDotOrgMappingInterface $mapping): void {
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    $schema_type = $mapping->getSchemaType();

    $form_modes = $this->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      $form_display->schemaDotOrgType = $schema_type;
      $form_display->save();
      unset($form_display->schemaDotOrgType);
    }

    $view_modes = $this->getViewModes($entity_type_id, $bundle);
    foreach ($view_modes as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      $view_display->schemaDotOrgType = $schema_type;
      $view_display->save();
      unset($view_display->schemaDotOrgType);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldDisplays(
    array $field,
    ?string $widget_id,
    array $widget_settings,
    ?string $formatter_id,
    array $formatter_settings,
  ): void {
    $schema_type = $field['schema_type'];
    $schema_property = $field['schema_property'];
    $entity_type_id = $field['entity_type'];
    $bundle = $field['bundle'];

    $mapping_type = $this->loadMappingType($entity_type_id);

    // Form display.
    if ($widget_id !== static::COMPONENT_HIDDEN) {
      $form_modes = $this->getFormModes($entity_type_id, $bundle);
      foreach ($form_modes as $form_mode) {
        $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
        $this->setComponent($form_display, $field, $widget_id, $widget_settings);
        $form_display->schemaDotOrgField = $field;
        $form_display->save();
        unset($form_display->schemaDotOrgField);
      }
    }

    // View display.
    if ($formatter_id !== static::COMPONENT_HIDDEN) {
      $view_modes = $this->getViewModes($entity_type_id, $bundle);
      foreach ($view_modes as $view_mode) {
        $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);

        $display_properties = $mapping_type->getSchemaTypeViewDisplayProperties($schema_type, $view_mode);
        if ($display_properties) {
          if (!isset($display_properties[$schema_property])) {
            continue;
          }

          // Alter text with summary field to show trimmed summary.
          $field_type = $field['type'] ?? NULL;
          if ($field_type === 'text_with_summary') {
            $formatter_id = 'text_summary_or_trimmed';
            $formatter_settings = ['label' => 'hidden'];
          }
        }

        $this->setComponent($view_display, $field, $formatter_id, $formatter_settings);
        $view_display->schemaDotOrgField = $field;
        $view_display->save();
        unset($view_display->schemaDotOrgField);
      }
    }
  }

  /**
   * Set entity display component.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param array $field
   *   The field definition.
   * @param string|null $type
   *   The component's plugin id.
   * @param array $settings
   *   The component's plugin settings.
   */
  protected function setComponent(EntityDisplayInterface $display, array $field, ?string $type, array $settings): void {
    $options = [];

    // Set custom component type.
    if ($type) {
      $options['type'] = $type;
    }

    // Converted some $settings to $options.
    if (!empty($settings)) {
      if ($display instanceof EntityViewDisplayInterface) {
        $option_names = ['label', 'weight', 'third_party_settings'];
      }
      else {
        $option_names = ['weight', 'third_party_settings'];
      }
      foreach ($option_names as $option_name) {
        if (isset($settings[$option_name])) {
          $options[$option_name] = $settings[$option_name];
          unset($settings[$option_name]);
        }
      }
      $options['settings'] = $settings;
    }

    $entity_type_id = $field['entity_type'];
    $bundle = $field['bundle'];
    $field_name = $field['field_name'];
    $schema_type = $field['schema_type'];
    $schema_property = $field['schema_property'];

    // Disable adding component to entity form/view display based on configurable settings.
    $disable_entity_display = $this->configFactory
      ->get('schemadotorg.settings')
      ->get('schema_properties.disable_entity_display');
    $parts = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => $field_name,
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
      'display_type' => $display instanceof EntityViewDisplayInterface ? 'view' : 'form',
      'display_mode' => $display->getMode(),
    ];
    if ($this->schemaTypeManager->getSetting($disable_entity_display, $parts, [], static::PATTERNS)) {
      return;
    }

    $options['weight'] = $options['weight']
      ?? $this->getSchemaPropertyDefaultFieldWeight($entity_type_id, $bundle, $field_name, $schema_type, $schema_property);
    $display->setComponent($field_name, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayComponentWeights(EntityDisplayInterface $display): ?array {
    $entity_type_id = $display->getTargetEntityTypeId();
    $bundle = $display->getTargetBundle();
    if (!$this->loadMapping($entity_type_id, $bundle)) {
      return NULL;
    }

    $mapping_type = $this->loadMappingType($entity_type_id);
    if (!$mapping_type
      || !$mapping_type->get('default_component_weights_update')) {
      return NULL;
    }

    $modes = $this->getModes($display);
    if (!isset($modes[$display->getMode()])) {
      return NULL;
    }

    $weights = $mapping_type->getDefaultComponentWeights();
    foreach ($weights as $name => $weight) {
      $component = $display->getComponent($name);
      if ($component) {
        continue;
      }

      $field_group = $display->getThirdPartySetting('field_group', $name);
      if ($field_group) {
        continue;
      }

      unset($weights[$name]);
    }
    return $weights;
  }

  /**
   * {@inheritdoc}
   */
  public function updateDisplayComponentWeights(EntityDisplayInterface $display): void {
    $weights = $this->getDisplayComponentWeights($display);
    if (!$weights) {
      return;
    }

    $components = $display->getComponents();
    foreach ($weights as $name => $weight) {
      if (isset($components[$name])) {
        $component = $display->getComponent($name);
        $component['weight'] = $weight;
        $display->setComponent($name, $component);
      }

      $field_group = $display->getThirdPartySetting('field_group', $name);
      if ($field_group) {
        $field_group['weight'] = $weight;
        $display->setThirdPartySetting('field_group', $name, $field_group);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterEntityDisplayEditForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\field_ui\Form\EntityFormDisplayEditForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface|null $display */
    $display = $form_object->getEntity();

    $weights = $this->getDisplayComponentWeights($display);
    if (empty($weights)) {
      return;
    }

    $human_names = [];
    foreach ($weights as $component_name => $weight) {
      $human_name = NestedArray::getValue($form, [
          'fields',
          $component_name,
          'human_name',
          '#plain_text',
      ]) ?? NestedArray::getValue($form, [
          'fields',
          $component_name,
          'human_name',
          '#markup',
      ]);

      if ($human_name) {
        $human_names[$component_name] = $human_name;
        $suffix = NestedArray::getValue(
          $form,
          ['fields', $component_name, 'human_name', '#suffix']
        ) ?? '';
        $suffix .= '<div><small>' . $this->t('(Weight: @weight)', ['@weight' => $weight]) . '</small></div>';

        NestedArray::setValue(
          $form,
          ['fields', $component_name, 'human_name', '#suffix'],
          $suffix,
        );
      }
    }

    if ($this->requestStack->getCurrentRequest()->isMethod('GET')) {
      $mapping_type = $this->loadMappingType($display->getTargetEntityTypeId());
      $t_args = [
        ':href' => $mapping_type->toUrl('edit-form')
          ->setOption('query', $this->redirectDestination->getAsArray())
          ->toString(),
        '%components' => implode('; ', $human_names),
      ];
      $this->messenger->addWarning(
        $this->t('The following components have hard code weights: %components. <a href=":href">Configure default component weights</a>', $t_args)
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setComponentWeights(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    $mapping_type = $this->loadMappingType($entity_type_id);
    if (!$mapping_type) {
      return;
    }

    $component_weights = $mapping_type->getDefaultComponentWeights();
    if (empty($component_weights)) {
      return;
    }

    $this->setDisplayComponentWeights($entity_type_id, $bundle, 'form', $component_weights);
    $this->setDisplayComponentWeights($entity_type_id, $bundle, 'view', $component_weights);
  }

  /**
   * Set the display component weights.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   * @param string $display_type
   *   The entity display type.
   * @param array $component_weights
   *   The entity display component weights.
   */
  protected function setDisplayComponentWeights(string $entity_type_id, string $bundle, string $display_type, array $component_weights): void {
    $display_type = ucfirst($display_type);
    $modes_method = "get{$display_type}Modes";
    $display_method = "get{$display_type}Display";

    $modes = $this->$modes_method($entity_type_id, $bundle);
    foreach ($modes as $mode) {
      $display = $this->entityDisplayRepository->$display_method($entity_type_id, $bundle, $mode);
      $is_updated = FALSE;
      foreach ($component_weights as $component_name => $component_weight) {
        $component = $display->getComponent($component_name);
        if ($component && isset($component['region'])) {
          $component['weight'] = $component_weight;
          $display->setComponent($component_name, $component);
          $is_updated = TRUE;
        }
      }
      if ($is_updated) {
        $display->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModes(EntityDisplayInterface $display): array {
    $entity_type_id = $display->getTargetEntityTypeId();
    $bundle = $display->getTargetBundle();
    return ($display instanceof EntityFormDisplayInterface)
        ? $this->getFormModes($entity_type_id, $bundle)
        : $this->getDisplayModes($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModes(string $entity_type_id, string $bundle): array {
    return $this->getDisplayModes(
      $entity_type_id,
      $bundle,
      'Form',
      []
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes(string $entity_type_id, string $bundle): array {
    $default_view_modes = ['teaser', 'content_browser'];
    return $this->getDisplayModes(
      $entity_type_id,
      $bundle,
      'View',
      $default_view_modes
    );
  }

  /**
   * Get display modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $type
   *   The display modes.
   * @param array $default_modes
   *   An array of default display modes.
   *
   * @return array
   *   An array of display modes.
   */
  protected function getDisplayModes(string $entity_type_id, string $bundle, string $type = 'View', array $default_modes = []): array {
    $mode_method = "get{$type}ModeOptionsByBundle";
    $mode_options = $this->entityDisplayRepository->$mode_method($entity_type_id, $bundle);

    if ($default_modes) {
      $modes = array_intersect_key(
        array_combine($default_modes, $default_modes),
        $mode_options
      );
    }
    else {
      $mode_keys = array_keys($mode_options);
      $modes = array_combine($mode_keys, $mode_keys);
    }

    return ['default' => 'default'] + $modes;
  }

}
