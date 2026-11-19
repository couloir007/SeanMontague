<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterManagerInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
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
    'image',
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

  /**
   * Saves an entity and returns it freshly loaded from storage.
   *
   * Port of FieldKernelTestBase::entitySaveReload(). This base extends
   * KernelTestBase rather than FieldKernelTestBase on purpose — the latter
   * pulls in the entity_test and field_test modules and a second entity-type
   * convention for the sake of two small helpers — so the helpers are provided
   * here instead.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to save.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity, reloaded from storage.
   */
  protected function entitySaveReload(FieldableEntityInterface $entity): FieldableEntityInterface {
    $entity->save();
    $id = $entity->id();
    $this->assertNotNull($id, 'The saved entity has an id.');

    $storage = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $storage->resetCache();
    $reloaded = $storage->load($id);
    $this->assertInstanceOf(FieldableEntityInterface::class, $reloaded, 'The entity was reloaded from storage.');

    return $reloaded;
  }

  /**
   * Validates and saves an entity, failing if there are any violations.
   *
   * Port of FieldKernelTestBase::entityValidateAndSave().
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to validate and save.
   */
  protected function entityValidateAndSave(FieldableEntityInterface $entity): void {
    $violations = $entity->validate();
    if ($violations->count() > 0) {
      $this->fail((string) $violations);
    }
    $entity->save();
  }

  /**
   * Asserts that an entity validates with no violations.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to validate.
   * @param string $context
   *   Optional context prefixed to the failure message, e.g. a case label.
   */
  protected function assertNoViolations(FieldableEntityInterface $entity, string $context = ''): void {
    $violations = $entity->validate();
    $this->assertCount(0, $violations, trim($context . ' Expected no violations, got: ' . (string) $violations));
  }

  /**
   * Asserts a single expected violation on a given subfield property.
   *
   * Mirrors what the test-plugin violation tuples assert today: the number of
   * violations, that the failing property path ends at the expected property,
   * and the exact (tag-stripped) message.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to validate.
   * @param string $property
   *   The subfield property expected to fail, e.g. 'sub' or 'sub__alt'.
   * @param string $expected_message
   *   The expected violation message, with any markup stripped.
   * @param int $expected_count
   *   Expected number of violations. Defaults to 1. A few types legitimately
   *   produce more than one for a single bad value — `decimal` raises both a
   *   primitive-type violation and a Range violation for a non-numeric string,
   *   because it carries a Range constraint whose validator also rejects
   *   non-numeric input. Such cases pass the real count through deliberately,
   *   rather than having the extra violations silently tolerated.
   * @param string $context
   *   Optional context prefixed to failure messages, e.g. a case label.
   */
  protected function assertViolation(
    FieldableEntityInterface $entity,
    string $property,
    string $expected_message,
    int $expected_count = 1,
    string $context = '',
  ): void {
    $violations = $entity->validate();
    $this->assertCount($expected_count, $violations, trim($context . ' Unexpected violation count. Got: ' . (string) $violations));

    $violation = $violations[0];
    $this->assertNotNull($violation, trim($context . ' A violation was returned.'));

    $path = explode('.', $violation->getPropertyPath());
    $this->assertSame($property, end($path), trim($context . ' The violation is on the expected property.'));
    $this->assertSame(
      $expected_message,
      strip_tags((string) $violation->getMessage()),
      trim($context . ' The violation message matches.'),
    );
  }

  /**
   * Asserts that generated sample values for a field validate and save.
   *
   * @param string $field_name
   *   The custom field machine name.
   * @param string $bundle
   *   The node bundle to create.
   */
  protected function assertGeneratedSampleSaves(string $field_name, string $bundle = 'page'): void {
    $node = Node::create([
      'type' => $bundle,
      'title' => 'Generated sample for ' . $field_name,
    ]);
    $node->get($field_name)->generateSampleItems();
    $this->entityValidateAndSave($node);

    $this->assertNotNull(
      $node->id(),
      sprintf('Generated sample values for "%s" validated and saved.', $field_name),
    );
  }

}
