<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;
use Drupal\schemadotorg_export\Traits\SchemaDotOrgExportTrait;
use Drupal\schemadotorg_starterkit\SchemaDotOrgStarterkitManagerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org starter kit export.
 */
class SchemaDotOrgExportStarterKitController extends ControllerBase {
  use SchemaDotOrgMappingStorageTrait;
  use SchemaDotOrgExportTrait;

  /**
   * Constructs a SchemaDotOrgExportStarterKitController object.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $schemaMappingManager
   *   The Schema.org mapping manager.
   * @param \Drupal\schemadotorg_starterkit\SchemaDotOrgStarterkitManagerInterface $schemaStarterKitManager
   *   The Schema.org starter kit manager.
   */
  public function __construct(
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgMappingManagerInterface $schemaMappingManager,
    protected SchemaDotOrgStarterkitManagerInterface $schemaStarterKitManager,
  ) {}

  /**
   * Returns response for Schema.org recipe CSV export request.
   *
   * @param string $name
   *   The name of the Schema.org mapping set.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed HTTP response containing a Schema.org recipe CSV export.
   */
  public function details(string $name): StreamedResponse {
    $settings = $this->schemaStarterKitManager->getStarterkitSettings($name);
    if (!$settings) {
      throw new NotFoundHttpException();
    }

    return $this->exportTypes($settings['types'], $name);
  }

}
