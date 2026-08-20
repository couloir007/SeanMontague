<?php

declare(strict_types=1);

namespace Drupal\schemadotorg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Schema.org autocomplete routes.
 */
class SchemaDotOrgAutocompleteController extends ControllerBase {

  /**
   * Constructs a SchemaDotOrgAutocompleteController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity type bundle information.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected Connection $database,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * Returns response for Schema.org (types or properties) autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $table
   *   Types or properties table name.
   * @param string $entity_type_id
   *   (Optional) Entity type ID to include matching bundle machine names.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, string $table, string $entity_type_id = ''): JsonResponse {
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse([]);
    }

    if ($this->schemaTypeManager->isType($table)) {
      $labels = $this->getBundleLabels($input, $entity_type_id);
      $children = array_keys($this->schemaTypeManager->getAllTypeChildren($table, ['label'], ['Enumeration']));
      sort($children);
      foreach ($children as $child) {
        if (stripos($child, $input) !== FALSE) {
          $labels[$child] = ['value' => $child, 'label' => $child];
        }
        if (count($labels) === 10) {
          break;
        }
      }
      return new JsonResponse(array_values($labels));
    }
    else {
      $query = $this->database->select('schemadotorg_' . $table, $table);
      $query->addField($table, 'label', 'value');
      $query->addField($table, 'label', 'label');
      $query->condition('label', '%' . $input . '%', 'LIKE');
      $query->orderBy('label');
      $query->range(0, 10);
      $labels = $query->execute()->fetchAllAssoc('label');
      return new JsonResponse(array_values($labels));
    }
  }

  /**
   * Gets matching entity bundle labels.
   *
   * @param string $input
   *   The autocomplete input.
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return array
   *   Matching bundle labels keyed by bundle machine name.
   */
  protected function getBundleLabels(string $input, string $entity_type_id): array {
    if (!$entity_type_id) {
      return [];
    }

    $labels = [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach (array_keys($bundles) as $bundle) {
      if (stripos($bundle, $input) !== FALSE) {
        $labels[$bundle] = ['value' => $bundle, 'label' => $bundle];
      }
      if (count($labels) === 10) {
        break;
      }
    }
    return $labels;
  }

}
