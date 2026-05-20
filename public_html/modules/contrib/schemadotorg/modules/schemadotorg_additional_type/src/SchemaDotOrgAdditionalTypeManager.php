<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Drupal\schemadotorg\Utility\SchemaDotOrgElementHelper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Schema.org additional type manager.
 */
class SchemaDotOrgAdditionalTypeManager implements SchemaDotOrgAdditionalTypeManagerInterface {
  use StringTranslationTrait;
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Constructs a SchemaDotOrgAdditionalTypeManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RequestStack $requestStack,
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingDefaultsAlter(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void {
    // Handle existing additional type property mapping.
    $mapping = $this->loadMapping($entity_type_id, $bundle);
    if ($mapping && $mapping->hasSchemaPropertyMapping('additionalType')) {
      return;
    }

    $allowed_values = $this->getAllowedValues($entity_type_id, $bundle, $schema_type);
    if (empty($allowed_values)) {
      return;
    }

    // Make sure the mapping defaults have an additionalType because
    // enumerations don't have an additional type.
    if (!isset($defaults['properties']['additionalType'])) {
      return;
    }

    $additional_type_property =& $defaults['properties']['additionalType'];

    // Get machine name.
    $machine_name = $this->getAdditionalTypeMachineName($additional_type_property['machine_name'], $schema_type);

    // Set name to 'add' field or default to an existing additional type field.
    $additional_type_property['name'] = (!$mapping && $this->isAdditionalTypeDefault($entity_type_id, $bundle, $schema_type))
      ? SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD
      : '';

    // Set field label.
    $additional_type_property['label'] = (string) $this->t('Type');

    // Set field type.
    $additional_type_property['type'] = 'list_string';

    // Set allowed values.
    $additional_type_property['allowed_values'] = $allowed_values;

    // Set required.
    $additional_type_property['required'] = $this->isAdditionalTypeRequired(
      $entity_type_id,
      $bundle,
      $schema_type
    );

    // Set machine name.
    $additional_type_property['machine_name'] = $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMappingForm(array &$form, FormStateInterface &$form_state): void {
    if (!$this->moduleHandler->moduleExists('schemadotorg_ui')) {
      return;
    }

    /** @var \Drupal\schemadotorg\Form\SchemaDotOrgMappingForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null $mapping */
    $mapping = $form_object->getEntity();

    // Exit if no Schema.org type has been selected.
    $schema_type = $mapping->getSchemaType();
    if (!$schema_type) {
      return;
    }

    // Make sure the Schema.org type has subtypes or default allowed values.
    $allowed_values = $this->getAllowedValues(
      $mapping->getTargetEntityTypeId(),
      $mapping->getTargetBundle(),
      $mapping->getSchemaType()
    );
    if (empty($allowed_values)) {
      return;
    }

    // Set additional type defaults from mapping defaults in $form_state.
    // @see \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm::buildFieldTypeForm
    $mapping_defaults = $form_state->get('mapping_defaults');
    $additional_type_defaults = $mapping_defaults['properties']['additionalType'] ?? NULL;

    // Make sure the current Schema.org type supports additional typing.
    if (empty($additional_type_defaults)) {
      return;
    }

    // Determine if Schema.org type already has additional typing enabled and
    // display additional typing information.
    if ($additional_type_defaults['name'] && $additional_type_defaults['name'] !== static::ADD_FIELD) {
      $form['mapping']['additional_type'] = [
        '#type' => 'item',
        '#title' => $this->t('Schema.org additional type'),
        '#markup' => $this->t('Enabled'),
        '#input' => FALSE,
        '#weight' => -5,
      ];
      $form['mapping']['additional_type']['name'] = [
        '#type' => 'value',
        '#parents' => ['mapping', 'properties', 'additionalType', 'field', 'name'],
        '#default_value' => $additional_type_defaults['name'],
      ];
      return;
    }

    // Get the add element.
    $add_element = $form['mapping']['properties']['additionalType']['field'][static::ADD_FIELD];

    // Remove the additionalType from the properties table because the field will
    // be mapped here.
    unset($form['mapping']['properties']['additionalType']);

    // Recreate additional type add field widget.
    $form['mapping']['additional_type'] = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org additional type'),
      '#open' => ($mapping->isNew() && $additional_type_defaults['name']),
      '#weight' => -5,
    ];
    $form['mapping']['additional_type']['name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Schema.org additional type'),
      '#description' => $this->t("If checked, a 'Type' field is added to the entity which allows content authors to specify a more specific type for the entity."),
      '#return_value' => static::ADD_FIELD,
      '#parents' => ['mapping', 'properties', 'additionalType', 'field', 'name'],
      '#default_value' => $additional_type_defaults['name'],
      '#element_validate' => [[static::class, 'validateForm']],
    ];

    // Append the additional type's add field properties.
    $form['mapping']['additional_type'][static::ADD_FIELD] = $add_element;

    // Adjust the #states trigger to use the checkbox.
    $form['mapping']['additional_type'][static::ADD_FIELD]['#states'] = [
      'visible' => [
        ':input[name="mapping[properties][additionalType][field][name]"]' => ['checked' => TRUE],
      ],
    ];

    // Set the details summary to display the hard coded field type.
    $form['mapping']['additional_type'][static::ADD_FIELD]['#attributes'] = ['data-schemadotorg-ui-summary' => $this->t('List (text)')];

    // Set required.
    $form['mapping']['additional_type'][static::ADD_FIELD]['required']['#default_value'] = $this->isAdditionalTypeRequired(
      $mapping->getTargetEntityTypeId(),
      $mapping->getTargetBundle(),
      $mapping->getSchemaType()
    );

    // Hard-code the field type.
    $form['mapping']['additional_type'][static::ADD_FIELD]['type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => $this->t('List (text)'),
      '#value' => $additional_type_defaults['type'],
    ];

    // Add the allowed values property.
    $form['mapping']['additional_type'][static::ADD_FIELD]['allowed_values'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Allowed values'),
      '#description' => '<p>' . $this->t('Enter allowed Schema.org additional types.') . '</p>'
        . '<p>'
        . $this->t('The possible values this field can contain. Enter one value per line, in the format key|label.') . '<br/>'
        . $this->t('The key is the stored value. The label will be used in displayed values and edit forms.') . '<br/>'
        . $this->t('The label is optional: if a line contains a single string, it will be used as key and label.')
        . '</p>'
        . '<p>' . $this->t('Allowed HTML tags in labels: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '</p>',
      '#required' => TRUE,
      '#default_value' => $additional_type_defaults['allowed_values'],
      '#example' => '
SubType: Sub Type
AdditionalType: Additional Type
',
    ];

    // Set the #parents for all the add field properties.
    SchemaDotOrgElementHelper::setElementParents(
      $form['mapping']['additional_type'][static::ADD_FIELD],
      ['mapping', 'properties', 'additionalType', 'field', static::ADD_FIELD]
    );
  }

  /**
   * Validate the Schema.org mapping form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function validateForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm $form_object */
    $form_object = $form_state->getFormObject();
    $mapping = $form_object->getEntity();
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle()
      ?: $form_state->getValue(['mapping', 'entity', 'id']);

    $field_values = $form_state->getValue(['mapping', 'properties', 'additionalType', 'field']);
    if ($field_values['name'] !== static::ADD_FIELD) {
      return;
    }

    $machine_name = $field_values[static::ADD_FIELD]['machine_name'];

    /** @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names */
    $schema_names = \Drupal::service('schemadotorg.names');
    $field_name = $schema_names->getFieldPrefix() . $machine_name;
    $field_storage_exists = (bool) FieldStorageConfig::loadByName($entity_type_id, $field_name);
    $field_exists = (bool) FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    if ($field_storage_exists || $field_exists) {
      $form_state->setValue(['mapping', 'properties', 'additionalType', 'field', 'name'], $field_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreate(EntityInterface $entity, bool $override = FALSE): void {
    // Only content entities support default field values.
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    // Check that the entity type has a Schema.org mapping type.
    if (!$this->loadMappingType($entity->getEntityTypeId())) {
      return;
    }

    // Support all entity types so that all default values can be stored
    // in one place.
    $parts = [
      'entity_type_id' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
    ];
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if ($mapping) {
      $additional_type_field_name = $mapping->getSchemaPropertyFieldName('additionalType');
      $additional_type = $entity->hasField($additional_type_field_name)
        ? $entity->get($additional_type_field_name)->value
        : NULL;
      $parts += [
        'schema_type' => $mapping->getSchemaType(),
        'additional_type' => $additional_type,
      ];
    }

    $default_field_values = $this->getDefaultFieldValues($parts);
    foreach ($default_field_values as $field_name => $value) {
      $field_name = $mapping?->getSchemaPropertyFieldName($field_name, TRUE)
        ?? $field_name;

      // Check if the entity has this field defined.
      if (!$entity->hasField($field_name)) {
        return;
      }

      // Skip updating the field if $override is FALSE (not forcing update)
      // and the field already has a value (is not empty).
      if (!$override && !$entity->get($field_name)->isEmpty()) {
        continue;
      }

      // Handle field values that are associative arrays (non-sequential keys)
      if (is_array($value) && !isset($value[0])) {
        // Directly set the field value since it's already properly structured.
        $entity->get($field_name)->setValue($value);
      }
      else {
        // Get the field definition to determine the main property name.
        $field_definition = $entity->get($field_name)->getFieldDefinition();
        $main_property = $field_definition->getFieldStorageDefinition()->getMainPropertyName();

        // Ensure value is an array for consistent processing.
        $value = (array) $value;

        // Process each value item to ensure proper field structure.
        foreach ($value as $index => $item) {
          // If the item is not an array, wrap it in an array with the main property key
          // This converts simple values into the expected field value structure.
          if (!is_array($item)) {
            $value[$index] = [$main_property => $item];
          }
        }

        // Set the properly structured field values on the entity.
        $entity->set($field_name, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function nodePrepareForm(NodeInterface $node, string $operation, FormStateInterface $form_state): void {
    if (!$node->access('update')) {
      return;
    }

    $mapping = $this->getMappingStorage()->loadByEntity($node);
    if (!$mapping) {
      return;
    }

    $is_required = $this->isAdditionalTypeRequired(
      $mapping->getTargetEntityTypeId(),
      $mapping->getTargetBundle(),
      $mapping->getSchemaType()
    );
    if (!$is_required) {
      return;
    }

    $field_name = $mapping->getSchemaPropertyFieldName('additionalType');
    if (!$field_name || !$node->hasField($field_name)) {
      return;
    }

    $value = $this->requestStack->getCurrentRequest()->query->get($field_name);
    if (!$value) {
      return;
    }

    $node->get($field_name)->value = $value;
    if ($node->isNew()) {
      $this->entityCreate($node);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function menuLinksDiscoveredAlter(array &$links): void {
    foreach ($links as $link_id => &$link) {
      if (!str_starts_with($link_id, 'navigation.content.node_type.')) {
        continue;
      }

      $route_name = $link['route_name'] ?? NULL;
      if ($route_name !== 'node.add') {
        continue;
      }

      $node_type = $link['route_parameters']['node_type'] ?? NULL;
      if ($this->isNodeTypeAdditionalTypeRequired($node_type)) {
        $this->setLinkOptionsAttributes($link);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function linkAlter(array &$variables): void {
    /** @var \Drupal\Core\Url|null $url */
    $url = $variables['url'] ?? NULL;
    if (!$url
      || !$url->isRouted()
      || $url->getRouteName() !== 'node.add'
      || !empty($url->getOption('query'))) {
      return;
    }

    $node_type = $url->getRouteParameters()['node_type'] ?? '';
    if ($this->isNodeTypeAdditionalTypeRequired($node_type)) {
      $this->setLinkOptionsAttributes($variables);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessLinks(array &$variables): void {
    // Only alter Schema.org Blueprints Entity Prepopulate node links.
    // @see \Drupal\schemadotorg_epp\SchemaDotOrgEppManager::buildNodeLinks
    if (!str_ends_with($variables['theme_hook_original'], '__schemadotorg_epp')) {
      return;
    }

    foreach ($variables['links'] as &$item) {
      $link =& $item['link'];
      /** @var \Drupal\Core\Url $url */
      $url = $link['#url'];
      $route_parameters = $url->getRouteParameters();
      $node_type = $route_parameters['node_type'] ?? NULL;
      if ($this->isNodeTypeAdditionalTypeRequired($node_type)
        && !$this->isNodeTypeAdditionalTypeInUrl($node_type, $url)) {
        $this->setLinkOptionsAttributes($link, '#options');
      }
    }
  }

  /**
   * Sets AJAX-related attributes to the link's options array.
   *
   * @param array $link
   *   The link array that will be modified to include AJAX attributes.
   * @param string $key
   *   The key in the link array where the options should be added. Defaults to 'options'.
   */
  protected function setLinkOptionsAttributes(array &$link, string $key = 'options'): void {
    $link += [$key => []];
    $link[$key] += ['attributes' => []];
    $link[$key]['attributes']['class'][] = 'use-ajax';
    $link[$key]['attributes']['data-dialog-type'] = 'modal';
    $link[$key]['attributes']['data-dialog-options'] = Json::encode(['width' => 600]);
  }

  /**
   * {@inheritdoc}
   */
  public function isAdditionalTypeRequired(string $entity_type_id, ?string $bundle, string $schema_type): bool {
    $required_types = $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('required_types');
    $parts = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'schema_type' => $schema_type,
    ];
    return (bool) $this->schemaTypeManager->getSetting($required_types, $parts, ['parents' => FALSE]);
  }

  /**
   * {@inheritdoc}
   */
  public function isNodeTypeAdditionalTypeRequired(string $node_type): bool {
    $mapping = $this->getMappingStorage()->loadByBundle('node', $node_type);
    if (!$mapping
      || !$mapping->hasSchemaPropertyMapping('additionalType', TRUE)) {
      return FALSE;
    }

    if ($this->isMappingAdditionalTypeMultiple($mapping)) {
      return FALSE;
    }

    return $this->isAdditionalTypeRequired(
      $mapping->getTargetEntityTypeId(),
      $mapping->getTargetBundle(),
      $mapping->getSchemaType()
    );
  }

  /**
   * Determines if the additional type Schema.org field name for a node type exists in the URL.
   *
   * @param string $node_type
   *   The node type.
   * @param \Drupal\Core\Url $url
   *   The URL object.
   *
   * @return bool
   *   TRUE if the additional type Schema.org field name for a node type
   *   exists in the URL.
   */
  protected function isNodeTypeAdditionalTypeInUrl(string $node_type, Url $url): bool {
    $mapping = $this->getMappingStorage()->loadByBundle('node', $node_type);
    if (!$mapping
      || !$mapping->hasSchemaPropertyMapping('additionalType', TRUE)) {
      return FALSE;
    }

    $additional_type_field_name = $mapping->getSchemaPropertyFieldName('additionalType');
    $query = $url->getOption('query');
    return !empty($query[$additional_type_field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function isMappingAdditionalTypeMultiple(SchemaDotOrgMappingInterface $mapping): bool {
    $field_storage_config = $this->getFieldStorageConfig($mapping);
    return $field_storage_config && $field_storage_config->isMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingAdditionalTypeAllowedValues(SchemaDotOrgMappingInterface $mapping): ?array {
    $field_storage_config = $this->getFieldStorageConfig($mapping);
    return ($field_storage_config)
      ? options_allowed_values($field_storage_config) ?: NULL
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldValues(array $parts): array {
    $default_field_values = $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('default_field_values');
    $multiple_default_field_values = $this->schemaTypeManager->getSetting($default_field_values, $parts, ['parents' => FALSE, 'multiple' => TRUE]);
    if (!$multiple_default_field_values) {
      return [];
    }

    $field_values = [];
    foreach ($multiple_default_field_values as $default_field_values) {
      $field_values += $default_field_values;
    }

    return $field_values;
  }

  /**
   * Retrieves the field storage configuration for a Schema.org mapping's property.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping entity for which the field storage configuration
   *   is being retrieved.
   *
   * @return \Drupal\field\FieldStorageConfigInterface|null
   *   The field storage configuration associated with the specified Schema.org
   *   property, or NULL if no configuration exists for the property.
   */
  protected function getFieldStorageConfig(SchemaDotOrgMappingInterface $mapping): ?FieldStorageConfigInterface {
    $field_name = $mapping->getSchemaPropertyFieldName('additionalType');
    if (!$field_name) {
      return NULL;
    }

    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
    $field_storage_config = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->load("node.$field_name");
    return $field_storage_config;
  }

  /**
   * Check if the additional type is enabled by default for a given Schema.org type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The Schema.org type.
   * @param string $schema_type
   *   The bundle name.
   *
   * @return bool
   *   TRUE if the additional type is enabled by default, FALSE otherwise.
   */
  protected function isAdditionalTypeDefault(string $entity_type_id, ?string $bundle, string $schema_type): bool {
    // Set name to 'add' field or default to an existing additional type field.
    $default_types = $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('default_types');
    $parts = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'schema_type' => $schema_type,
    ];
    return (bool) $this->schemaTypeManager->getSetting($default_types, $parts, ['parents' => FALSE]);
  }

  /**
   * Get allowed values for a Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The entity bundle.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return array
   *   An array of allowed values.
   */
  protected function getAllowedValues(string $entity_type_id, ?string $bundle, string $schema_type): array {
    $allowed_values = $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('default_allowed_values');
    $parts = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'schema_type' => $schema_type,
    ];
    $allowed_values = $this->schemaTypeManager->getSetting($allowed_values, $parts, ['parents' => FALSE]);
    if ($allowed_values) {
      return $allowed_values;
    }

    $use_snake_case = $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('use_snake_case');
    $allowed_values = $this->schemaTypeManager->getAllTypeChildrenAsOptions($schema_type);
    if (!$use_snake_case) {
      return $allowed_values;
    }

    $snake_case_allowed_values = [];
    foreach ($allowed_values as $key => $value) {
      $snake_case_allowed_values[$this->schemaNames->camelCaseToSnakeCase($key)] = $value;
    }
    return $snake_case_allowed_values;
  }

  /**
   * Get the machine name for the additional type.
   *
   * @param string $machine_name
   *   The default machine name.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return string
   *   The machine name for the additional type.
   */
  protected function getAdditionalTypeMachineName(string $machine_name, string $schema_type): string {
    if ($machine_name !== 'additional_type') {
      return $machine_name;
    }

    $machine_name_max_length = $this->schemaNames->getNameMaxLength('properties') - strlen(static::FIELD_NAME_SUFFIX);
    $options = [
      'maxlength' => $machine_name_max_length,
      'truncate' => TRUE,
    ];
    $machine_name = $this->schemaNames->camelCaseToDrupalName($schema_type, $options);
    $machine_name .= static::FIELD_NAME_SUFFIX;
    return $machine_name;
  }

}
