<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_embedded_content\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\embedded_content\EmbeddedContentPluginBase;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base plugin for Schema.org Blueprints embedded content.
 */
abstract class SchemaDotOrgEmbeddedContentBase extends EmbeddedContentPluginBase implements SchemaDotOrgEmbeddedContentInterface {

  use StringTranslationTrait;

  /**
   * The component ID.
   */
  protected string $componentId;

  /**
   * The Schema.org type.
   */
  protected string $schemaType;

  /**
   * The Schema.org properties.
   */
  protected array $schemaProperties;

  /**
   * The Schema.org schema names services.
   */
  protected SchemaDotOrgNamesInterface $schemaNames;

  /**
   * The Schema.org schema type manager.
   */
  protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager;

  /**
   * The Schema.org field manager service.
   */
  protected SchemaDotOrgEntityFieldManagerInterface $schemaFieldManager;

  /**
   * The component plugin manager.
   */
  protected ComponentPluginManager $componentManager;

  /**
   * {@inheritdoc}
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->schemaNames = $container->get('schemadotorg.names');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaFieldManager = $container->get('schemadotorg.entity_field_manager');
    $instance->componentManager = $container->get('plugin.manager.sdc');
    $instance->setConfiguration($configuration);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = [];
    $component_properties = $this->getComponentProperties();
    foreach ($component_properties as $component_property => $component_property_definition) {
      $default_configuration[$component_property] = $component_property_definition['default'] ?? '';
    }
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $component_properties = $this->getComponentProperties();
    foreach ($component_properties as $component_property => $component_property_definition) {
      $element_title = $component_property_definition['title'];
      $element_description = $component_property_definition['description'];
      $element_required = $this->isElementRequired($component_property);

      $form[$component_property] = $this->getElementDefaults($component_property) + [
        '#title' => $this->t($element_title),
        '#description' => $this->t($element_description),
        '#required' => $element_required,
        '#default_value' => $this->configuration[$component_property],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $component = $this->getComponentId();
    $props = array_intersect_key($this->configuration, $this->getComponentProperties());
    return [
      '#type' => 'component',
      '#component' => $component,
      '#props' => $props,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isInline(): bool {
    return FALSE;
  }

  /* ************************************************************************ */
  // JSON-LD methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function getJsonId(): array {
    $data = [];
    $data['@type'] = (!empty($this->configuration['subtype'])) ? $this->configuration['subtype'] : $this->schemaType;
    foreach ($this->schemaProperties as $schema_property) {
      $component_property = $this->schemaNames->camelCaseToSnakeCase($schema_property);
      if (isset($this->configuration[$component_property])) {
        $data[$schema_property] = $this->configuration[$component_property];
      }
    }
    return $data;
  }

  /* ************************************************************************ */
  // Element methods.
  /* ************************************************************************ */

  /**
   * Determine if a form element is required.
   *
   * @param string $property
   *   The component's property name.
   *
   * @return bool
   *   TRUE if the form element is required.
   */
  protected function isElementRequired(string $property): bool {
    $definition = $this->getComponentDefinition();
    return in_array($property, $definition['props']['required'] ?? []);
  }

  /**
   * Get a form element's defaults.
   *
   * @param string $property
   *   The component's property name.
   *
   * @return array
   *   A form element's defaults.
   */
  protected function getElementDefaults(string $property): array {
    // Get element type from Schema.org property's default field.
    $schema_property = $this->schemaNames->snakeCaseToCamelCase($property);
    if ($this->schemaTypeManager->isProperty($schema_property)) {
      $default_field = $this->schemaFieldManager
        ->getPropertyDefaultField('paragraph', $this->schemaType, $schema_property);

      $field_type = $default_field['type'] ?? NULL;
      switch ($field_type) {
        case 'text_long':
          return ['#type' => 'textarea'];
      }
    }

    $property_definition = $this->getComponentProperty($property);
    if (isset($property_definition['enum'])) {
      return [
        '#type' => 'select',
        '#options' => array_combine($property_definition['enum'], $property_definition['enum']),
      ];
    }

    switch ($property_definition['type']) {
      case 'boolean':
        return [
          '#type' => 'checkbox',
          '#return_value' => TRUE,
        ];

      case 'number':
        return ['#type' => 'number'];

      case 'string':
      default:
        return ['#type' => 'textfield'];
    }
  }

  /* ************************************************************************ */
  // Component methods.
  /* ************************************************************************ */

  /**
   * Get the component ID.
   *
   * @return string
   *   The component ID.
   */
  protected function getComponentId(): string {
    return $this->componentId;
  }

  /**
   * Get the component definition.
   *
   * @return array
   *   The component definition.
   */
  protected function getComponentDefinition(): array {
    return $this->componentManager->getDefinition($this->getComponentId());
  }

  /**
   * Get the component's properties.
   *
   * @return array
   *   The component's properties.
   */
  protected function getComponentProperties(): array {
    $definition = $this->getComponentDefinition();
    return $definition['props']['properties'];
  }

  /**
   * Get the component's property definition.
   *
   * @param string $property
   *   The component property.
   *
   * @return array
   *   The component's property definition.
   */
  protected function getComponentProperty(string $property): array {
    $definition = $this->getComponentDefinition();
    return $definition['props']['properties'][$property];
  }

}
