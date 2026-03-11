<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_field_group;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field_group\Form\FieldGroupAddForm;
use Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org field group entity display builder.
 */
class SchemaDotOrgFieldGroupEntityDisplayBuilder implements SchemaDotOrgFieldGroupEntityDisplayBuilderInterface {
  use StringTranslationTrait;

  /**
   * Cached look up of default field group names by Schema.org property/field name.
   */
  protected array $defaultFieldGroupNames;

  /**
   * Cached look up of default field group weights by Schema.org property/field name.
   */
  protected array $defaultFieldGroupWeights;

  /**
   * Constructs a SchemaDotOrgFieldGroupEntityDisplayBuilder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
   *   The redirect destination service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface $schemaEntityDisplayBuilder
   *   The Schema.org entity display builder.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected MessengerInterface $messenger,
    protected RedirectDestinationInterface $redirectDestination,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgEntityDisplayBuilderInterface $schemaEntityDisplayBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingApply(SchemaDotOrgMappingInterface $mapping): void {
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    $schema_type = $mapping->getSchemaType();

    // Skip mappings that have fields groups disabled to improve performance.
    $disable_field_groups = $this->configFactory
      ->get('schemadotorg_field_group.settings')
      ->get('disable_field_groups');
    if ($disable_field_groups && $this->schemaTypeManager->getSetting(
      settings: $disable_field_groups,
      parts: ['entity_type_id' => $entity_type_id, 'bundle' => $bundle, 'schema_type' => $schema_type],
      patterns: SchemaDotOrgEntityDisplayBuilderInterface::PATTERNS,
    )) {
      return;
    }

    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    if ($mapping->getTargetEntityTypeId() === 'node') {
      unset($base_field_definitions['title']);
    }
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $field_definitions = array_diff_key($field_definitions, $base_field_definitions);

    $field_mappings = [];
    foreach (array_keys($field_definitions) as $field_name) {
      $field_mappings[$field_name] = $mapping->getSchemaPropertyMapping($field_name, TRUE) ?? '';
    }

    // Form displays.
    $form_modes = $this->schemaEntityDisplayBuilder->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      foreach ($field_mappings as $field_name => $schema_property) {
        $this->setFieldGroup($form_display, $field_name, $schema_type, $schema_property);
      }
      $form_display->save();
    }

    // View displays.
    $view_modes = $this->schemaEntityDisplayBuilder->getViewModes($entity_type_id, $bundle);
    foreach ($view_modes as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      foreach ($field_mappings as $field_name => $schema_property) {
        $this->setFieldGroup($view_display, $field_name, $schema_type, $schema_property);
      }
      $view_display->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingPreSave(SchemaDotOrgMappingInterface $mapping): void {
    if ($mapping->isSyncing()) {
      return;
    }

    if (!$mapping->isNew() || $mapping->getTargetEntityTypeId() !== 'node') {
      return;
    }

    // Set form and view display for existing title and body fields.
    $schema_type = $mapping->getSchemaType();
    $schema_properties = array_intersect_key(
      $mapping->getNewSchemaProperties(),
      ['title' => 'title', 'body' => 'body'],
    );
    if (!$schema_properties) {
      return;
    }

    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    foreach ($schema_properties as $field_name => $schema_property) {
      // Form display.
      $form_modes = $this->schemaEntityDisplayBuilder->getFormModes($entity_type_id, $bundle);
      foreach ($form_modes as $form_mode) {
        $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
        $this->setFieldGroup($form_display, $field_name, $schema_type, $schema_property);
        $form_display->save();
      }

      // View display.
      $view_modes = $this->schemaEntityDisplayBuilder->getViewModes($entity_type_id, $bundle);
      foreach ($view_modes as $view_mode) {
        $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
        $this->setFieldGroup($view_display, $field_name, $schema_type, $schema_property);
        $view_display->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityDisplayPreSave(EntityDisplayInterface $display): void {
    if ($display->isSyncing()) {
      return;
    }

    $field = $display->schemaDotOrgField ?? NULL;
    if (!$field) {
      return;
    }

    $field_name = $field['field_name'];
    $schema_type = $field['schema_type'];
    $schema_property = $field['schema_property'];

    $modes = $this->schemaEntityDisplayBuilder->getModes($display);
    // Only support field groups in the default and full view modes.
    if ($display instanceof EntityViewDisplayInterface) {
      $modes = array_intersect_key($modes, ['default' => 'default', 'full' => 'full']);
    }
    if (isset($modes[$display->getMode()])) {
      $this->setFieldGroup($display, $field_name, $schema_type, $schema_property);
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
    $entity_type_id = $display->getTargetEntityTypeId();
    $bundle = $display->getTargetBundle();

    $display_type = ($display instanceof EntityFormDisplayInterface) ? 'form' : 'view';
    $mode = $display->getMode();

    // Do not display warning message when field groups are disabled
    // the current entity form/view display.
    $disable_field_groups = $this->configFactory
      ->get('schemadotorg_field_group.settings')
      ->get('disable_field_groups');
    if ($disable_field_groups && $this->schemaTypeManager->getSetting(
        settings: $disable_field_groups,
        parts: ['entity_type_id' => $entity_type_id, 'bundle' => $bundle, 'display_type' => $display_type, 'mode' => $mode],
        patterns: SchemaDotOrgEntityDisplayBuilderInterface::PATTERNS,
      )) {
      return;
    }

    $url = Url::fromRoute(
      'schemadotorg.settings.properties',
      [], [
        'fragment' => 'edit-schemadotorg-field-group',
        'query' => $this->redirectDestination->getAsArray(),
      ]
    );
    $t_args = [':href' => $url->toString()];
    $this->messenger->addWarning($this->t('The below field groups are created and maintained using the Schema.org Blueprints Field Group module. <a href=":href">Configure field group settings</a>', $t_args));
  }

  /**
   * Set entity display field groups for a Schema.org property.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $schema_type
   *   The field name's associated Schema.org type.
   * @param string $schema_property
   *   The field name's associated Schema.org property.
   *
   * @see field_group_group_save()
   * @see field_group_field_overview_submit()
   * @see \Drupal\field_group\Form\FieldGroupAddForm::submitForm
   */
  protected function setFieldGroup(EntityDisplayInterface $display, string $field_name, string $schema_type, string $schema_property): void {
    if (!$this->hasFieldGroup($display, $field_name, $schema_type, $schema_property)) {
      return;
    }

    $entity_type_id = $display->getTargetEntityTypeId();
    $bundle = $display->getTargetBundle();
    $display_type = ($display instanceof EntityFormDisplayInterface) ? 'form' : 'view';
    $field_group = $this->getFieldGroup(
      $entity_type_id,
      $bundle,
      $field_name,
      $schema_type,
      $schema_property
    );
    if (!$field_group) {
      return;
    }

    $field_weight = $this->getFieldWeight(
      $entity_type_id,
      $bundle,
      $field_name,
      $schema_type,
      $schema_property
    );

    // Prefix group name.
    $group_name = FieldGroupAddForm::GROUP_PREFIX . $field_group['name'];
    $group_label = $field_group['label'];
    $group_weight = $field_group['weight'];
    $group_description = $field_group['description'] ?? '';

    // Remove field name from an existing groups, so that it can be reset.
    $existing_groups = $display->getThirdPartySettings('field_group');
    foreach ($existing_groups as $existing_group_name => $existing_group) {
      $index = array_search($field_name, $existing_group['children']);
      if ($index !== FALSE) {
        array_splice($existing_group['children'], $index, 1);
        $display->setThirdPartySetting('field_group', $existing_group_name, $existing_group);
      }
    }

    // Get existing group.
    $group = $display->getThirdPartySetting('field_group', $group_name);
    if (!$group) {
      $group_format_type = $field_group[$display_type . '_type']
        ?? $this->configFactory
          ->get('schemadotorg_field_group.settings')
          ->get('default_' . $display_type . '_type')
        ?: '';
      $group_format_settings = $field_group[$display_type . '_settings'] ?? [];
      if ($group_format_type === 'details') {
        $group_format_settings += ['open' => TRUE];
      }
      if ($display instanceof EntityFormDisplayInterface) {
        $group_format_settings += ['description' => $group_description];
      }
      $group = [
        'label' => $group_label,
        'children' => [],
        'parent_name' => '',
        'weight' => $group_weight,
        'format_type' => $group_format_type,
        'format_settings' => $group_format_settings,
        'region' => 'content',
      ];
    }

    // Make sure the tabs group is defined.
    if ($group['format_type'] === 'tab') {
      $tabs_name = FieldGroupAddForm::GROUP_PREFIX . 'tabs';
      $tabs_group = $display->getThirdPartySetting('field_group', $tabs_name)
        ?? $this->configFactory
          ->get('schemadotorg_field_group.settings')
          ->get('default_field_groups.' . $entity_type_id . '.tabs')
        ?? [];
      $tabs_group += [
        'label' => (string) $this->t('Tabs'),
        'children' => [],
        'parent_name' => '',
        'weight' => 0,
        'format_type' => 'tabs',
        'format_settings' => ['direction' => 'horizontal'],
        'region' => 'content',
      ];
      $tabs_group['children'][] = $group_name;
      $tabs_group['children'] = array_unique($tabs_group['children']);
      $display->setThirdPartySetting('field_group', $tabs_name, $tabs_group);

      $group['parent_name'] = $tabs_name;
    }

    // Append the field to the children.
    $group['children'][] = $field_name;
    $group['children'] = array_unique($group['children']);

    // Set field group in the entity display.
    $display->setThirdPartySetting('field_group', $group_name, $group);

    // Set field component's weight.
    $component = $display->getComponent($field_name);
    $component['weight'] = $field_weight;
    $display->setComponent($field_name, $component);

    // Update default field groups for ungrouped Schema.org properties.
    $config = $this->configFactory
      ->getEditable('schemadotorg_field_group.settings');
    if ($config->get('update_default_field_groups')
      && !$this->getDefaultFieldGroupName($entity_type_id, $bundle, $field_name, $schema_type, $schema_property)) {
      $default_group_name = str_replace(FieldGroupAddForm::GROUP_PREFIX, '', $group_name);
      $default_field_groups = $config->get('default_field_groups.' . $entity_type_id);
      $default_field_groups += [$default_group_name => []];
      $default_field_groups[$default_group_name] += [
        'label' => $group_label,
        'weight' => $group_weight,
        'properties' => [],
      ];
      if (!in_array($field_name, $default_field_groups[$default_group_name]['properties'])) {
        uasort($default_field_groups, [SortArray::class, 'sortByWeightElement']);
        $default_field_groups[$default_group_name]['properties'][] = $field_name;
        $config->set('default_field_groups.' . $entity_type_id, $default_field_groups)
          ->save();
        unset($this->defaultFieldGroupNames);
        unset($this->defaultFieldGroupWeights);
      }
    }
  }

  /**
   * Get the field group for a given entity type, field name, Schema.org type, Schema.org property, and mapping values.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return array
   *   An array containing the field group name, label, and weight.
   */
  protected function getFieldGroup(string $entity_type_id, string $bundle, string $field_name, string $schema_type, string $schema_property): array {
    // Automatically generate a default catch all field group for
    // the current Schema.org type.
    $group_name = $this->getFieldGroupName($entity_type_id, $bundle, $field_name, $schema_type, $schema_property);
    if ($group_name === FALSE) {
      return [];
    }
    elseif (is_null($group_name)) {
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface|null $mapping_type */
      $mapping_type = $this->entityTypeManager
        ->getStorage('schemadotorg_mapping_type')
        ->load($entity_type_id);
      // But don't generate a group for default fields.
      $base_field_names = $mapping_type->getBaseFieldNames();
      if (isset($base_field_names[$field_name])) {
        return [];
      }

      return [
        'name' => $this->schemaNames->schemaIdToDrupalName('types', $schema_type),
        'label' => $this->schemaNames->camelCaseToSentenceCase($schema_type),
        'description ' => '',
        'weight' => 0,
        'form_type' => NULL,
        'form_settings' => NULL,
        'view_type' => NULL,
        'view_settings' => NULL,
      ];
    }
    else {
      $default_field_groups = $this->configFactory
        ->get('schemadotorg_field_group.settings')
        ->get('default_field_groups.' . $entity_type_id) ?? [];
      return [
        'name' => $group_name,
        'label' => $default_field_groups[$group_name]['label'] ?? $group_name,
        'description' => $default_field_groups[$group_name]['description'] ?? '',
        'weight' => $default_field_groups[$group_name]['weight'] ?? 0,
        'form_type' => $default_field_groups[$group_name]['form_type'] ?? NULL,
        'form_settings' => $default_field_groups[$group_name]['form_settings'] ?? NULL,
        'view_type' => $default_field_groups[$group_name]['form_type'] ?? NULL,
        'view_settings' => $default_field_groups[$group_name]['form_settings'] ?? NULL,
      ];
    }
  }

  /**
   * Get the default field group name for a given entity, field, Schema.org type, Schema.org property, and mapping values.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return string|null
   *   The field group name or null if not found.
   */
  protected function getDefaultFieldGroupName(string $entity_type_id, string $bundle, string $field_name, string $schema_type, string $schema_property): string|null {
    if (empty($this->defaultFieldGroupNames)) {
      $this->defaultFieldGroupNames = [];
      $default_field_groups = $this->configFactory
        ->get('schemadotorg_field_group.settings')
        ->get('default_field_groups.' . $entity_type_id) ?? [];
      foreach ($default_field_groups as $default_field_group_name => $default_field_group) {
        $this->defaultFieldGroupNames += array_fill_keys($default_field_group['properties'], $default_field_group_name);
      }
    }

    $parts = [
      'bundle' => $bundle,
      'field_name' => $field_name,
      'schema_type' => $schema_type,
      'schema_property' => explode(':', $schema_property)[0],
    ];
    return $this->schemaTypeManager->getSetting($this->defaultFieldGroupNames, $parts);
  }

  /**
   * Get the field group name for a given entity, field, Schema.org type, Schema.org property, and mapping values.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return string|bool|null
   *   The field group name, FALSE for no group, or null if not found.
   */
  protected function getFieldGroupName(string $entity_type_id, string $bundle, string $field_name, string $schema_type, string $schema_property): string|bool|null {
    $default_field_group_name = $this->getDefaultFieldGroupName($entity_type_id, $bundle, $field_name, $schema_type, $schema_property);
    if ($default_field_group_name) {
      return $default_field_group_name;
    }

    // Get default field groups.
    $default_field_groups = $this->configFactory
      ->get('schemadotorg_field_group.settings')
      ->get('default_field_groups.' . $entity_type_id) ?? [];

    // Set group name for sub properties of identifier.
    if (isset($default_field_groups['identifiers'])
      && $this->schemaTypeManager->isSubPropertyOf($schema_property, 'identifier')
    ) {
      return 'identifiers';
    }

    // Set group name by field type.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->load("$entity_type_id.$field_name");
    if ($field_storage) {
      $field_type = $field_storage->getType();
      $field_target_type = $field_storage->getSetting('target_type');

      if ($field_type === 'link' && isset($default_field_groups['links'])) {
        return 'links';
      }
      elseif ($field_type === 'entity_reference') {
        if ($field_target_type === 'taxonomy_term' && isset($default_field_groups['taxonomy'])) {
          return 'taxonomy';
        }
        elseif (isset($default_field_groups['relationships'])) {
          return 'relationships';
        }
      }
    }

    // Set group name by the parent Schema.org type.
    $default_schema_type_field_groups = $this->configFactory
      ->get('schemadotorg_field_group.settings')
      ->get('default_schema_type_field_groups');
    foreach ($default_schema_type_field_groups as $default_schema_type => $default_field_group_name) {
      if (isset($default_field_groups[$default_field_group_name])
        && $this->schemaTypeManager->isSubTypeOf($schema_type, $default_schema_type)) {
        return $default_field_group_name;
      }
    }

    return NULL;
  }

  /**
   * Retrieves the weight of a field in the field group.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The name of the field.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return int
   *   The weight of the field in the field group.
   */
  protected function getFieldWeight(string $entity_type_id, string $bundle, string $field_name, string $schema_type, string $schema_property): int {
    if (!isset($this->defaultFieldGroupWeights)) {
      $this->defaultFieldGroupWeights = [];
      $default_field_groups = $this->configFactory
        ->get('schemadotorg_field_group.settings')
        ->get('default_field_groups.' . $entity_type_id) ?? [];
      foreach ($default_field_groups as $default_field_group) {
        $this->defaultFieldGroupWeights += array_flip($default_field_group['properties']);
      }
    }

    // Get the main Schema.org property.
    // (i.e., 'name' is the main property for 'name:prefix'.)
    $schema_property = explode(':', $schema_property)[0];

    $parts = [
      'bundle' => $bundle,
      'field_name' => $field_name,
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
    ];
    return $this->schemaTypeManager->getSetting($this->defaultFieldGroupWeights, $parts)
      ?? $this->schemaEntityDisplayBuilder->getSchemaPropertyDefaultFieldWeight($entity_type_id, $bundle, $field_name, $schema_type, $schema_property);
  }

  /**
   * Determine if the Schema.org property/field name has field group.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $schema_type
   *   The field name's associated Schema.org type.
   * @param string $schema_property
   *   The field name's associated Schema.org property.
   *
   * @return bool
   *   TRUE if the Schema.org property/field name has field group
   */
  protected function hasFieldGroup(EntityDisplayInterface $display, string $field_name, string $schema_type, string $schema_property): bool {
    if (!$display->getComponent($field_name)) {
      return FALSE;
    }

    $disable_field_groups = $this->configFactory
      ->get('schemadotorg_field_group.settings')
      ->get('disable_field_groups');
    if (empty($disable_field_groups)) {
      return TRUE;
    }

    $parts = [
      'entity_type_id' => $display->getTargetEntityTypeId(),
      'bundle' => $display->getTargetBundle(),
      'schema_type' => $schema_type,
      'schema_property' => explode(':', $schema_property)[0],
      'field_name' => $field_name,
      'display_type' => ($display instanceof EntityFormDisplayInterface) ? 'form' : 'view',
      'display_mode' => $display->getMode(),
    ];

    return !$this->schemaTypeManager->getSetting(
      settings: $disable_field_groups,
      parts: $parts,
      patterns: SchemaDotOrgEntityDisplayBuilderInterface::PATTERNS,
    );
  }

}
