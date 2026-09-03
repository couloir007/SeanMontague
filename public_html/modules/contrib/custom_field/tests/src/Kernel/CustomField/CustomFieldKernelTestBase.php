<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterManagerInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;

/**
 * Base class for Custom Field plugin Kernel tests.
 */
abstract class CustomFieldKernelTestBase extends KernelTestBase {

  use CustomFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'custom_field',
  ];

  /**
   * The custom field type plugin manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected CustomFieldTypeManagerInterface $typeManager;

  /**
   * The custom field widget plugin manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldWidgetManagerInterface
   */
  protected CustomFieldWidgetManagerInterface $widgetManager;

  /**
   * The custom field formatter plugin manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldFormatterManagerInterface
   */
  protected CustomFieldFormatterManagerInterface $formatterManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);

    // Adjust these service IDs to match the actual module.
    $this->typeManager = $this->container->get('plugin.manager.custom_field_type');
    $this->widgetManager = $this->container->get('plugin.manager.custom_field_widget');
    $this->formatterManager = $this->container->get('plugin.manager.custom_field_formatter');

    // Minimal content type so we can create real field definitions.
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
  }

  /**
   * Helper to create a FieldItemList for testing.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $values
   *   Values keyed by delta. For a custom field each delta should be an
   *   associative array of subfield machine names => values.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The populated item list.
   */
  protected function createItemList(FieldDefinitionInterface $field_definition, array $values = []): FieldItemListInterface {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = \Drupal::typedDataManager()->create($field_definition, $values);
    return $items;
  }

  /**
   * Returns configured CustomFieldType plugins for a field definition.
   *
   * Same path CustomItem uses internally.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface[]
   *   Keyed by subfield machine name.
   */
  protected function getCustomFieldItems(FieldDefinitionInterface $field_definition): array {
    return $this->typeManager->getCustomFieldItems($field_definition->getSettings());
  }

}
