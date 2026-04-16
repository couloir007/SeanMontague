<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeManagerInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Returns responses for Schema.org Blueprints Additional Type routes.
 */
class SchemaDotOrgAdditionalTypeReportController extends ControllerBase {
  use SchemaDotOrgMappingStorageTrait;

  /**
   * The Schema.org schema type manager.
   */
  protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager;

  /**
   * The Schema.org additional type manager.
   */
  protected SchemaDotOrgAdditionalTypeManagerInterface $additionalTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->additionalTypeManager = $container->get('schemadotorg_additional_type.manager');
    return $instance;
  }

  /* ************************************************************************ */
  // Table.
  /* ************************************************************************ */

  /**
   * Generates a table with Schema.org mappings and additional type information.
   *
   * @return array
   *   A render array containing the table and download link.
   */
  public function index(): array {
    $field_names = $this->getFieldNames();

    // Header.
    $header = [
      'entity_type_id' => ['data' => $this->t('Entity type')],
      'bundle' => ['data' => $this->t('Bundle')],
      'schema_type' => ['data' => $this->t('Schema.org type')],
      'additional_type' => ['data' => $this->t('Schema.org additional type')],
    ];
    foreach ($field_names as $field_name) {
      $header[$field_name] = ['data' => $field_name];
    }
    $header['operation'] = '';

    $rows = [];
    /** @var \Drupal\node\NodeTypeInterface[] $node_types */
    $node_types = $this->entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $bundle => $node_type) {
      $row = [];
      $row['entity_type_id'] = 'node';
      $row['bundle'] = $node_type->id();
      $row['schema_type'] = '';
      $row['additional_type'] = '';
      // Add empty values for each field name.
      $row += array_fill_keys($field_names, '');

      // Load Schema.org mapping for the given node bundle.
      $mapping = $this->loadMapping('node', $bundle);
      if ($mapping) {
        // Set the schema type from the mapping and add a row with default field values.
        $row['schema_type'] = $mapping->getSchemaType();
        $rows[] = $this->setDefaultFieldValuesForTableRow($row, $mapping);

        // Get any additional types defined for this mapping.
        $additional_types = $this->getAdditionalTypes($mapping);
        if ($additional_types) {
          // Add a row for each additional type with the default field values.
          foreach (array_keys($additional_types) as $additional_type) {
            $row['additional_type'] = $additional_type;
            $rows[] = $this->setDefaultFieldValuesForTableRow($row, $mapping);
          }
        }
      }
      else {
        // If no mapping exists, add a row with just the default field values.
        $rows[] = $this->setDefaultFieldValuesForTableRow($row);
      }
    }

    $build = [];

    // Table.
    $build['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No Schema.org mappings found.'),
      '#attached' => ['library' => ['schemadotorg/codemirror.yaml']],
    ];

    // Download CSV link.
    $url = Url::fromRoute('schemadotorg_additional_type.report.download');
    $build['export'] = [
      '#type' => 'link',
      '#title' => $this->t('<u>⇩</u> Download CSV'),
      '#url' => $url,
      '#attributes' => ['class' => ['button', 'button--small', 'button--extrasmall']],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    return $build;
  }

  /**
   * Sets default field values for a given set of row data.
   *
   * @param array $row
   *   The row.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null $mapping
   *   The row's Schema.org mapping, if available.
   *
   * @return array
   *   The updated row with default field values.
   */
  protected function setDefaultFieldValuesForTableRow(array $row, ?SchemaDotOrgMappingInterface $mapping = NULL): array {
    $field_values = $this->getDefaultFieldValues($row);
    if (!$field_values) {
      return $this->setCreateButton($row, $mapping);
    }

    foreach ($field_values as $key => $value) {
      $row[$key] = [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#plain_text' => Yaml::encode($value),
          '#attributes' => ['data-schemadotorg-codemirror-mode' => 'text/x-yaml'],
        ],
      ];

      $field_name = $mapping?->getSchemaPropertyFieldName($key, TRUE) ?? $key;
      /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
      $field_storage = $this->entityTypeManager()
        ->getStorage('field_storage_config')
        ->load("node.$field_name");
      if (!$field_storage) {
        continue;
      }

      $target_type = $field_storage->getSetting('target_type');
      if (!$target_type) {
        continue;
      }

      if (is_array($value) && !isset($value[0])) {
        $value = [$value];
      }
      else {
        $value = (array) $value;
      }

      $items = [];
      foreach ($value as $item) {
        // Extract target ID from either array item or direct value.
        $target_id = (is_array($item) && isset($item['target_id'])) ? $item['target_id'] : $item;
        if (!$target_id) {
          continue;
        }

        // Load the target entity.
        $target_entity = $this->entityTypeManager()->getStorage($target_type)->load($target_id);
        if (!$target_entity) {
          continue;
        }

        // Skip if the target entity doesn't have a canonical URL template.
        if (!$target_entity->hasLinkTemplate('canonical')) {
          continue;
        }

        // Build a breadcrumb-style label for term hierarchy.
        if ($target_entity instanceof TermInterface) {
          $parents = [];
          $parent_term = $target_entity;
          while ($parent_term) {
            $parents[] = $parent_term->label();
            $parent_term = $parent_term->get('parent')->entity;
          }
          // Create label with parent terms separated by '﹥'
          // (e.g. "Parent > Child > Grandchild")
          $label = implode(' ﹥ ', array_reverse($parents));
        }
        else {
          // For non-taxonomy entities, use the entity's label.
          $label = $target_entity->label();
        }

        $items[] = $target_entity->toLink($label)->toRenderable();
      }
      if (count($items) <= 1) {
        $row[$key] = ['data' => $items];
      }
      else {
        $row[$key] = [
          'data' => [
            '#theme' => 'item_list',
            '#items' => $items,
          ],
        ];
      }
    }
    return $this->setCreateButton($row, $mapping);
  }

  /**
   * Adds a 'Create' button to a data row.
   *
   * @param array $row
   *   The row.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null $mapping
   *   The row's Schema.org mapping, if available.
   *
   * @return array
   *   The updated row with the 'Create' button..
   */
  protected function setCreateButton(array $row, ?SchemaDotOrgMappingInterface $mapping = NULL): array {
    // Add the 'Create' button.
    $url = Url::fromRoute('node.add', ['node_type' => $row['bundle']]);
    // Append the additional type to the URL if it's set.'.
    if (!empty($row['additional_type']) && !empty($mapping)) {
      $additional_type_field_name = $mapping->getSchemaPropertyFieldName('additionalType');
      $url->setOption('query', [$additional_type_field_name => $row['additional_type']]);
    }

    $row['operation'] = [
      'data' => [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $this->t('Create'),
        '#attributes' => [
          'class' => ['button', 'button--small', 'button--extrasmall'],
        ],
      ],
    ];

    return $row;
  }

  /* ************************************************************************ */
  // Download.
  /* ************************************************************************ */

  /**
   * Generates and streams a CSV file for download.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   The streamed response containing the CSV file for download.
   */
  public function download(): StreamedResponse {
    $response = new StreamedResponse(function (): void {
      $handle = fopen('php://output', 'r+');

      $field_names = $this->getFieldNames();

      // Header.
      $header = [
        'entity_type_id',
        'bundle',
        'schema_type',
        'additional_type',
      ];
      $header += $field_names;
      fputcsv($handle, $header);

      // Rows.
      /** @var \Drupal\node\NodeTypeInterface[] $node_types */
      $node_types = $this->entityTypeManager()->getStorage('node_type')->loadMultiple();
      foreach ($node_types as $bundle => $node_type) {
        $row = [];
        $row['entity_type_id'] = 'node';
        $row['bundle'] = $node_type->id();
        $row['schema_type'] = '';
        $row['additional_type'] = '';
        // Add empty values for each field name.
        $row += array_fill_keys($field_names, '');

        // Load Schema.org mapping for the given node bundle.
        $mapping = $this->loadMapping('node', $bundle);
        if ($mapping) {
          // Set the schema type from the mapping and add a row with default field values.
          $row['schema_type'] = $mapping->getSchemaType();
          fputcsv($handle, $this->setDefaultFieldValuesForCsvRow($row));

          // Get any additional types defined for this mapping.
          $additional_types = $this->getAdditionalTypes($mapping);
          if ($additional_types) {
            // Add a row for each additional type with the default field values.
            foreach (array_keys($additional_types) as $additional_type) {
              $row['additional_type'] = $additional_type;
              fputcsv($handle, $this->setDefaultFieldValuesForCsvRow($row));
            }
          }
        }
        else {
          // If no mapping exists, add a row with just the default field values.
          fputcsv($handle, $this->setDefaultFieldValuesForCsvRow($row));
        }
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="node-additional-types.csv"');
    return $response;
  }

  /**
   * Sets default field values for a CSV row based on provided settings.
   *
   * @param array $row
   *   The row.
   *
   * @return array
   *   The updated CSV row with default field values set.
   */
  protected function setDefaultFieldValuesForCsvRow(array $row): array {
    $field_values = $this->getDefaultFieldValues($row);
    foreach ($field_values as $field_name => $value) {
      $row[$field_name] = Yaml::encode($value);
    }
    return $row;
  }

  /* ************************************************************************ */
  // Helper methods.
  /* ************************************************************************ */

  /**
   * Retrieves default field values based on the provided row and settings.
   *
   * @param array $row
   *   The data row used to determine the settings for retrieving default field values.
   *
   * @return array
   *   An associative array of default field values derived from the provided settings.
   */
  protected function getDefaultFieldValues(array $row): array {
    // Get the settings search parts from the row.
    $parts = array_intersect_key(
      $row,
      array_flip([
        'entity_type_id',
        'bundle',
        'schema_type',
        'additional_type',
      ]),
    );
    return $this->additionalTypeManager->getDefaultFieldValues($parts);
  }

  /**
   * Retrieves the field names from the configuration settings.
   *
   * @return string[]
   *   An array containing the field names.
   */
  protected function getFieldNames(): array {
    $default_field_values = $this->config('schemadotorg_additional_type.settings')
      ->get('default_field_values');
    $field_names = [];
    foreach ($default_field_values as $default_field_value) {
      $keys = array_keys($default_field_value);
      $field_names += array_combine($keys, $keys);
    }
    return $field_names;
  }

  /**
   * Retrieves additional types for the given Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping interface instance used to fetch the additional
   *   types.
   *
   * @return array
   *   An associative array of allowed values for the additional type field, or
   *   an empty array if the field storage configuration could not be loaded.
   */
  protected function getAdditionalTypes(SchemaDotOrgMappingInterface $mapping): array {
    $additional_type_field_name = $mapping->getSchemaPropertyFieldName('additionalType');
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage */
    $field_storage = $this->entityTypeManager()
      ->getStorage('field_storage_config')
      ->load("node.$additional_type_field_name");
    return ($field_storage) ? options_allowed_values($field_storage) : [];
  }

}
