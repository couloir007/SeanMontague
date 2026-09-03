<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Traits;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;

/**
 * Shared helpers for Custom Field tests.
 */
trait CustomFieldTestTrait {

  /**
   * Creates a custom field storage + instance.
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $columns
   *   Storage columns setting.
   * @param array $field_settings
   *   Per-subfield field settings (type-level only).
   * @param string $entity_type
   *   Entity type id.
   * @param string $bundle
   *   Bundle name.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   The field config.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the field storage or field config fails to save.
   */
  protected function createCustomField(
    string $field_name,
    array $columns,
    array $field_settings = [],
    string $entity_type = 'node',
    string $bundle = 'page',
  ): FieldConfig {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'custom',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'columns' => $columns,
      ],
    ]);
    $storage->save();

    $field = FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => $bundle,
      'label' => $field_name,
      'settings' => [
        'field_settings' => $field_settings,
      ],
    ]);
    $field->save();

    // Ensure subsequent code in this request sees the new field.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    return $field;
  }

  /**
   * Updates field_settings on an existing custom field.
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $field_settings
   *   Full or partial per-subfield field settings to merge.
   * @param string $entity_type
   *   Entity type id.
   * @param string $bundle
   *   Bundle name.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   The updated field config.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the field config fails to save.
   */
  protected function updateFieldSettings(
    string $field_name,
    array $field_settings,
    string $entity_type = 'node',
    string $bundle = 'page',
  ): FieldConfig {
    $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    $this->assertNotNull($field, sprintf('Field %s.%s.%s not found.', $entity_type, $bundle, $field_name));

    $current = $field->getSetting('field_settings') ?? [];
    // Merge per subfield so you can change only 'description' etc.
    foreach ($field_settings as $subfield => $settings) {
      $current[$subfield] = [
        ...($current[$subfield] ?? []),
        ...$settings,
      ];
    }

    $field->setSetting('field_settings', $current);
    $field->save();

    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    return $field;
  }

  /**
   * Default wrapper settings for view display subfields.
   *
   * @return array
   *   Array of wrapper settings.
   */
  protected function defaultWrappers(): array {
    return [
      'field_wrapper_tag' => '',
      'field_wrapper_classes' => '',
      'field_tag' => '',
      'field_classes' => '',
      'label_tag' => '',
      'label_classes' => '',
    ];
  }

  /**
   * Loads a single node by its title.
   *
   * @param string $title
   *   The node title.
   *
   * @return \Drupal\node\NodeInterface
   *   The loaded node.
   */
  protected function loadNodeByTitle(string $title): NodeInterface {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    $node = reset($nodes);
    $this->assertNotFalse($node, sprintf('Node with title "%s" not found.', $title));
    /** @var \Drupal\node\NodeInterface $node */
    return $node;
  }

  /**
   * Reloads a node from storage, bypassing the static entity cache.
   *
   * @param int|string $id
   *   The node id.
   *
   * @return \Drupal\node\NodeInterface
   *   The freshly loaded node.
   */
  protected function reloadNode(string|int $id): NodeInterface {
    /** @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($id);
    return $node;
  }

}
