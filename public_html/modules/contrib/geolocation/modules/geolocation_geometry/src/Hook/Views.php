<?php

namespace Drupal\geolocation_geometry\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Hook implementations for geolocation_geometry module views functionality.
 */
class Views {

  /**
   * Implements hook_field_views_data().
   */
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {

    $geometry_fields = [];
    foreach (
      [
        'geolocation',
        'geolocation_geometry_geometry',
        'geolocation_geometry_polygon',
        'geolocation_geometry_multipolygon',
        'geolocation_geometry_linestring',
        'geolocation_geometry_multilinestring',
        'geolocation_geometry_point',
        'geolocation_geometry_multipoint',
      ] as $field_type
    ) {
      foreach (\Drupal::service('entity_field.manager')->getFieldMapByFieldType($field_type) as $entity_type_id => $geometry_field_list) {
        /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $entity_storage */
        $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
        $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
        $entity_table_mapping = $entity_storage->getTableMapping();
        $entity_label = $entity_definition->getLabel();
        foreach ($geometry_field_list as $geometry_field_name => $geometry_field_data) {
          $geometry_field_id = $entity_type_id . '__' . $geometry_field_name;
          $geometry_fields[$geometry_field_id] = $geometry_field_data;
          $geometry_fields[$geometry_field_id]['field_type'] = $field_type;
          $geometry_fields[$geometry_field_id]['entity_type'] = $entity_type_id;
          $geometry_fields[$geometry_field_id]['entity_id_field'] = $entity_definition->getKey('id');
          // @phpstan-ignore-next-line
          $geometry_fields[$geometry_field_id]['entity_data_table'] = $entity_table_mapping->getDataTable();
          $geometry_fields[$geometry_field_id]['entity_label'] = $entity_label;
          $geometry_fields[$geometry_field_id]['geometry_field_name'] = $geometry_field_name;
          $geometry_fields[$geometry_field_id]['geometry_field_table'] = $entity_table_mapping->getFieldTableName($geometry_field_name);
        }
      }
    }

    $entity_definition = \Drupal::entityTypeManager()->getDefinition($field_storage->getTargetEntityTypeId());
    $entity_type_storage = \Drupal::entityTypeManager()->getStorage($entity_definition->getBundleEntityType());

    $bundle_labels = [];
    foreach ($field_storage->getBundles() as $bundle_id) {
      $bundle_labels[] = $entity_type_storage->load($bundle_id)->label();
    }

    // Get the default data from the views module.
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

    $args = ['@field_name' => $field_storage->getName()];

    // Loop through all the results and set our overrides.
    foreach ($data as $table_name => $table_data) {
      $data[$table_name]['table']['entity type'] = $field_storage->getTargetEntityTypeId();
      foreach ($table_data as $field_name => $field_data) {
        // Only modify fields.
        if ($field_name == 'delta') {
          continue;
        }
        if (isset($field_data['filter'])) {
          if (str_ends_with($field_name, '_geometry')) {
            $data[$table_name][$field_name]['title'] = t('Geometry (@field_name)', $args);
            continue;
          }
          if (str_ends_with($field_name, '_geojson')) {
            $data[$table_name][$field_name]['title'] = t('GeoJSON (@field_name)', $args);
            continue;
          }
          // The default filters are mostly not useful except lat/lng.
          unset($data[$table_name][$field_name]['filter']);
        }
        if (isset($field_data['argument'])) {
          // The default arguments aren't useful at all so remove them.
          unset($data[$table_name][$field_name]['argument']);
        }
        if (isset($field_data['sort'])) {
          // The default arguments aren't useful at all so remove them.
          unset($data[$table_name][$field_name]['sort']);
        }
      }

      $title_short = '';

      $help = t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundle_labels)]);

      // Add proximity handlers.
      $data[$table_name][$args['@field_name'] . '_proximity'] = [
        'group' => $entity_definition->getLabel(),
        'title' => t('Geo Proximity (@field_name)', $args),
        'title short' => $title_short . ' - ' . t("Geo Proximity"),
        'help' => $help,
        'argument' => [
          'id' => 'geolocation_geometry_argument_proximity',
          'table' => $table_name,
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'field_name' => $args['@field_name'] . '_proximity',
          'real field' => $args['@field_name'],
          'label' => t('Geo Distance to !field_name', $args),
          'empty field name' => '- No value -',
          'additional fields' => [
            $args['@field_name'] . '_geometry',
          ],
        ],
        'filter' => [
          'id' => 'geolocation_geometry_filter_proximity',
          'table' => $table_name,
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'field_name' => $args['@field_name'] . '_proximity',
          'real field' => $args['@field_name'],
          'label' => t('Geo Distance to !field_name', $args),
          'allow empty' => TRUE,
          'additional fields' => [
            $args['@field_name'] . '_geometry',
          ],
        ],
        'field' => [
          'table' => $table_name,
          'id' => 'geolocation_geometry_field_proximity',
          'field_name' => $args['@field_name'] . '_proximity',
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'real field' => $args['@field_name'],
          'float' => TRUE,
          'additional fields' => [
            $args['@field_name'] . '_geometry',
          ],
          'element type' => 'div',
          'is revision' => (isset($table_data[$args['@field_name']]['field']['is revision']) && $table_data[$args['@field_name']]['field']['is revision']),
          'click sortable' => TRUE,
        ],
        'sort' => [
          'table' => $table_name,
          'id' => 'geolocation_sort_proximity',
          'field_name' => $args['@field_name'] . '_proximity',
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'real field' => $args['@field_name'],
        ],
      ];

      // Add boundary handlers.
      $data[$table_name][$args['@field_name'] . '_boundary'] = [
        'group' => $entity_definition->getLabel(),
        'title' => t('Geo Boundary (@field_name)', $args),
        'title short' => $title_short . ' - ' . t("Boundary"),
        'help' => $help,
        'argument' => [
          'id' => 'geolocation_geometry_argument_boundary',
          'table' => $table_name,
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'field_name' => $args['@field_name'] . '_boundary',
          'real field' => $args['@field_name'],
          'label' => t('Geo Boundaries around !field_name', $args),
          'empty field name' => '- No value -',
          'additional fields' => [
            $args['@field_name'] . '_geometry',
          ],
        ],
        'filter' => [
          'id' => 'geolocation_geometry_filter_boundary',
          'table' => $table_name,
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'field_name' => $args['@field_name'] . '_boundary',
          'real field' => $args['@field_name'],
          'label' => t('Geo Boundaries around !field_name', $args),
          'allow empty' => TRUE,
          'additional fields' => [
            $args['@field_name'] . '_geometry',
          ],
        ],
      ];

      foreach ($geometry_fields as $geometry_field_id => $geometry_field_data) {
        if ($field_storage->getName() == $geometry_field_data['geometry_field_name'] && $field_storage->getTargetEntityTypeId() == $geometry_field_data['entity_type']) {
          continue;
        }
        $data[$table_name][$geometry_field_id] = [
          'group' => t('Geolocation Geometry'),
          'title' => t('Geolocation Geometry'),
          'title short' => t('Geolocation Geometry'),
          'help' => $help,
          'relationship' => [
            'title' => t('@entity_type (@field_name) via @source_field_name', [
              '@source_field_name' => $field_storage->getName(),
              '@entity_type' => $geometry_field_data['entity_label'],
              '@field_name' => $geometry_field_data['geometry_field_name'],
            ]),
            'label' => t('@entity_type (@field_name) via @source_field_name', [
              '@source_field_name' => $field_storage->getName(),
              '@entity_type' => $geometry_field_data['entity_label'],
              '@field_name' => $geometry_field_data['geometry_field_name'],
            ]),
            'group' => t('Geolocation Geometry'),
            'id' => 'geolocation_geometry',
            'field_type' => $field_storage->getType(),
            'table' => $table_name,
            'field' => $field_storage->getName(),
            'relationship field' => $geometry_field_data['geometry_field_name'],
            'relationship table' => $geometry_field_data['geometry_field_table'],
            'base' => $geometry_field_data['entity_data_table'],
            'base field' => $geometry_field_data['entity_id_field'],
          ],
        ];
      }
    }

    return $data;
  }

  /**
   * Implements hook_field_views_data_views_data_alter().
   */
  #[Hook('field_views_data_views_data_alter')]
  public function fieldViewsDataViewsDataAlter(array &$data, FieldStorageConfigInterface $field_storage): void {
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $entity_storage */
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_table_mapping = $entity_storage->getTableMapping();
    $entity_label = $entity_definition->getLabel();
    $entity_type_storage = \Drupal::entityTypeManager()->getStorage($entity_definition->getBundleEntityType());

    $bundle_labels = [];
    foreach ($field_storage->getBundles() as $bundle_id) {
      $bundle_labels[] = $entity_type_storage->load($bundle_id)->label();
    }

    $geometry_field_id = $entity_type_id . '__' . $field_storage->getName();
    $geometry_field_data = [
      'field_type' => $field_storage->getType(),
      'entity_type' => $entity_type_id,
      'entity_id_field' => $entity_definition->getKey('id'),
      // @phpstan-ignore-next-line
      'entity_data_table' => $entity_table_mapping->getDataTable(),
      'entity_label' => $entity_label,
      'geometry_field_name' => $field_storage->getName(),
      'geometry_field_table' => $entity_table_mapping->getFieldTableName($field_storage->getName()),
    ];

    foreach (\Drupal::service('entity_field.manager')->getFieldMapByFieldType('geolocation') as $entity_type => $geolocation_fields) {
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $geolocation_table_mapping */
      // @phpstan-ignore-next-line
      $geolocation_table_mapping = \Drupal::entityTypeManager()->getStorage($entity_type)->getTableMapping();

      foreach ($geolocation_fields as $geolocation_field_name => $geolocation_field_data) {
        $geolocation_field_table_name = $geolocation_table_mapping->getFieldTableName($geolocation_field_name);
        if (empty($data[$geolocation_field_table_name])) {
          continue;
        }

        $help = t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundle_labels)]);

        $data[$geolocation_field_table_name][$geometry_field_id] = [
          'group' => t('Geolocation Geometry'),
          'title' => t('Geolocation Geometry'),
          'title short' => t('Geolocation Geometry'),
          'help' => $help,
          'relationship' => [
            'title' => t('@entity_type (@field_name) via @source_field_name', [
              '@source_field_name' => $geolocation_field_name,
              '@entity_type' => $geometry_field_data['entity_label'],
              '@field_name' => $geometry_field_data['geometry_field_name'],
            ]),
            'label' => t('@entity_type (@field_name) via @source_field_name', [
              '@source_field_name' => $geolocation_field_name,
              '@entity_type' => $geometry_field_data['entity_label'],
              '@field_name' => $geometry_field_data['geometry_field_name'],
            ]),
            'group' => t('Geolocation Geometry'),
            'id' => 'geolocation_geometry',
            'field_type' => 'geolocation',
            'table' => $geolocation_field_table_name,
            'field' => $geolocation_field_name,
            'relationship field' => $geometry_field_data['geometry_field_name'],
            'relationship table' => $geometry_field_data['geometry_field_table'],
            'base' => $geometry_field_data['entity_data_table'],
            'base field' => $geometry_field_data['entity_id_field'],
          ],
        ];
      }
    }

  }

}
