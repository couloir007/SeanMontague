<?php

namespace Drupal\geolocation_gpx\Hook;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Hook implementations for geolocation_gpx module views functionality.
 */
class Views {

  use StringTranslationTrait;

  /**
   * Constructs a new Views hook implementation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {
  }

  /**
   * Implements hook_field_views_data().
   *
   * Relates entities to the GPX referenced by their GPX field, and back.
   */
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {
    if (\Drupal::hasService('views.field_data_provider')) {
      $data = \Drupal::service('views.field_data_provider')->defaultFieldImplementation($field_storage);
    }
    // Compatibility with D10. Remove after D12.
    elseif (function_exists('views_field_default_views_data')) {
      // @phpstan-ignore-next-line
      $data = views_field_default_views_data($field_storage);
    }
    else {
      return [];
    }

    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $gpx_entity_type = $this->entityTypeManager->getDefinition('geolocation_gpx');
    $gpx_base_table = $gpx_entity_type->getDataTable() ?: $gpx_entity_type->getBaseTable();
    $field_name = $field_storage->getName();

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $entity_storage */
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $entity_storage->getTableMapping();
    $gpx_id_column = $table_mapping->getFieldColumnName($field_storage, 'gpx_id');

    $args = [
      '@label' => $gpx_entity_type->getLabel(),
      '@field_name' => $field_name,
    ];

    foreach (array_keys($data) as $table_name) {
      $data[$table_name][$field_name]['relationship'] = [
        'title' => $this->t('@label referenced from @field_name', $args),
        'label' => $this->t('@field_name: @label', $args),
        'group' => $entity_type->getLabel(),
        'help' => $this->t('Appears in: @bundles.', [
          '@bundles' => implode(', ', $field_storage->getBundles()),
        ]),
        'id' => 'standard',
        'base' => $gpx_base_table,
        'entity type' => 'geolocation_gpx',
        'base field' => $gpx_entity_type->getKey('id'),
        'relationship field' => $gpx_id_column,
      ];
    }

    $args['@entity'] = $entity_type->getLabel();
    $args['@label'] = $gpx_entity_type->getSingularLabel();
    $data[$gpx_base_table]['reverse__' . $entity_type_id . '__' . $field_name]['relationship'] = [
      'title' => $this->t('@entity using @field_name', $args),
      'label' => $this->t('@field_name', $args),
      'group' => $gpx_entity_type->getLabel(),
      'help' => $this->t('Relate each @entity with a @field_name set to the @label.', $args),
      'id' => 'entity_reverse',
      'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
      'entity_type' => $entity_type_id,
      'base field' => $entity_type->getKey('id'),
      'field_name' => $field_name,
      'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'field field' => $gpx_id_column,
      'join_extra' => [
        [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ];

    return $data;
  }

  /**
   * Implements hook_views_data_alter().
   *
   * GPX components only reference their children, so relate each child back
   * to the GPX, track, segment or route referencing it.
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (
        $entity_type->getProvider() !== 'geolocation_gpx'
        || !$entity_type instanceof ContentEntityTypeInterface
      ) {
        continue;
      }

      /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $entity_storage */
      $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $entity_storage->getTableMapping();

      foreach ($this->entityFieldManager->getBaseFieldDefinitions($entity_type_id) as $field_name => $field_definition) {
        $field_storage = $field_definition->getFieldStorageDefinition();
        if (
          $field_definition->getType() !== 'entity_reference'
          || !$table_mapping->requiresDedicatedTableStorage($field_storage)
        ) {
          continue;
        }

        $target_entity_type = $this->entityTypeManager->getDefinition($field_definition->getSetting('target_type'));
        if ($target_entity_type->getProvider() !== 'geolocation_gpx') {
          continue;
        }

        $args = [
          '@entity' => $entity_type->getLabel(),
          '@field_name' => $field_definition->getLabel(),
          '@label' => $target_entity_type->getSingularLabel(),
        ];
        $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
        $data[$target_base_table]['reverse__' . $entity_type_id . '__' . $field_name]['relationship'] = [
          'title' => $this->t('@entity using @field_name', $args),
          'label' => $this->t('@entity', $args),
          'group' => $target_entity_type->getLabel(),
          'help' => $this->t('Relate each @entity with @field_name containing the @label.', $args),
          'id' => 'entity_reverse',
          'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
          'entity_type' => $entity_type_id,
          'base field' => $entity_type->getKey('id'),
          'field_name' => $field_name,
          'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
          'field field' => $table_mapping->getFieldColumnName($field_storage, 'target_id'),
          'join_extra' => [
            [
              'field' => 'deleted',
              'value' => 0,
              'numeric' => TRUE,
            ],
          ],
        ];
      }
    }
  }

}
