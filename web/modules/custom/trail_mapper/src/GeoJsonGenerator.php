<?php

namespace Drupal\trail_mapper;

use Drupal\external_pg\ExternalPgService;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GeoJsonGenerator.
 *
 * Generates a GeoJSON file from external PostgreSQL data.
 */
class GeoJsonGenerator {
  use StringTranslationTrait;

  /**
   * The external PostgreSQL service.
   *
   * @var \Drupal\external_pg\ExternalPgService
   */
  protected ExternalPgService $externalPgService;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $extensionList;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs a new GeoJsonGenerator object.
   *
   * @param \Drupal\external_pg\ExternalPgService $externalPgService
   *   The external PostgreSQL connection service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionList
   *   The module extension list service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ExternalPgService $externalPgService, ModuleExtensionList $extensionList, MessengerInterface $messenger) {
    $this->externalPgService = $externalPgService;
    $this->extensionList = $extensionList;
    $this->messenger = $messenger;
  }

  /**
   * Generates the GeoJSON file.
   */
  public function generateGeoJson(): void {
    // Define your table name and spatial reference.
    $table = 'kt_trails';
    $scr = 6589;

    // Build the SQL query string.
    $query = "
      WITH tmp1 AS (
        SELECT 'Feature' AS \"type\",
          ST_AsGeoJSON(ST_Transform(ST_SetSRID(t.geom, {$scr}), 4326), 6)::json AS \"geometry\",
          (
            SELECT json_strip_nulls(row_to_json(t))
            FROM (
              SELECT objectid AS id, name, touches, ST_Length(ST_Transform(geom, {$scr})) AS length
            ) t
          ) AS \"properties\"
        FROM public.{$table} t
      ),
      tmp2 AS (
        SELECT 'FeatureCollection' AS \"type\",
          array_to_json(array_agg(t)) AS \"features\"
        FROM tmp1 t
      )
      SELECT row_to_json(t)
      FROM tmp2 t;
    ";

    // Use the external PostgreSQL service to fetch the data.
    $data = $this->externalPgService->fetchData($query);

    // Get the module path for trail_mapper.
    $module_path = $this->extensionList->getPath('trail_mapper');
    // Build the absolute path to the includes directory.
    $output = DRUPAL_ROOT . '/' . $module_path . '/includes/KingdomTrails.geojson';

    // Write the GeoJSON file.
    foreach ($data as $row) {
      if ($fp = fopen($output, 'w')) {
        fwrite($fp, $row->row_to_json);
        fclose($fp);
        $this->messenger->addStatus($this->t('GeoJSON file has been saved successfully.'));
      }
      else {
        $this->messenger->addError($this->t('Unable to open the file for writing.'));
      }
    }
  }
}
