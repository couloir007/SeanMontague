<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg_report\Traits\SchemaDotOrgReportBuildTrait;

/**
 * Returns responses for Schema.org report hierarchy routes.
 */
class SchemaDotOrgReportHierarchyController extends ControllerBase {
  use SchemaDotOrgReportBuildTrait;

  /**
   * Constructs a SchemaDotOrgReportHierarchyController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schemaTypeBuilder
   *   The Schema.org schema type builder.
   */
  public function __construct(
    protected Connection $database,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgSchemaTypeBuilderInterface $schemaTypeBuilder,
  ) {}

  /**
   * Builds the Schema.org types hierarchy.
   *
   * @param string $type
   *   The root Schema.org type.
   *
   * @return array
   *   A renderable array containing Schema.org types hierarchy.
   */
  public function index(string $type = 'Thing'): array {
    if ($type === 'DataTypes') {
      $types = $this->database->select('schemadotorg_types', 'types')
        ->fields('types', ['label'])
        ->condition('sub_type_of', '')
        ->condition('label', ['True', 'False', 'Thing'], 'NOT IN')
        ->orderBy('label')
        ->execute()
        ->fetchCol();
      $tree = $this->schemaTypeManager->getTypeTree($types);
      $count = count($this->schemaTypeManager->getDataTypes());
    }
    else {
      $ignored_types = ['Intangible', 'Enumeration', 'StructuredValue'];
      $ignored_types = array_combine($ignored_types, $ignored_types);
      unset($ignored_types[$type]);
      $tree = $this->schemaTypeManager->getTypeTree($type, $ignored_types);
      $count = count($this->schemaTypeManager->getAllTypeChildren($type, ['label'], $ignored_types));
    }
    $build = [];
    $build['info'] = $this->buildInfo($type, $count);
    $build['tree'] = $this->schemaTypeBuilder->buildTypeTree($tree);
    return $build;
  }

}
