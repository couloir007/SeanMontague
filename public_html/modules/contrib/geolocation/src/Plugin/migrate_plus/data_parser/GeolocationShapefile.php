<?php

namespace Drupal\geolocation\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\DataParserPluginBase;
use Shapefile\Shapefile;
use Shapefile\ShapefileReader;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "geolocation_shapefile",
 *   title = @Translation("Geolocation Shapefile")
 * )
 */
class GeolocationShapefile extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Shapefile.
   */
  protected ShapefileReader $shapefile;

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl(string $url): bool {
    if (isset($this->shapefile)) {
      return TRUE;
    }

    /** @var \Drupal\Core\TempStore\SharedTempStore $tempstore */
    $tempstore = \Drupal::service('tempstore.shared')->get('geolocation_shapefile');
    $shapefile_path = $tempstore->get($url) ?? NULL;

    if (!$shapefile_path || !is_file($shapefile_path) || ($this->configuration['geolocation_shapefile_force_redownload'] ?? FALSE)) {
      $response = $this->getDataFetcherPlugin()->getResponse($url);

      if ($response->getStatusCode() !== 200) {
        throw new \Exception("Could not download " . $url);
      }

      $tempfile = tmpfile();
      $tempfile_path = stream_get_meta_data($tempfile)['uri'];
      fwrite($tempfile, $response->getBody());

      $temp_directory = tempnam(sys_get_temp_dir(), 'geolocation_shapefile');
      unlink($temp_directory);
      mkdir($temp_directory);

      $zip = new \ZipArchive();
      if (!$zip->open($tempfile_path, \ZipArchive::CHECKCONS | \ZipArchive::RDONLY)) {
        throw new \Exception("Could not open ZIP");
      }

      $shapefile_name = FALSE;
      for ($i = 0; $i < $zip->numFiles; $i++) {
        if (pathinfo($zip->getNameIndex($i), PATHINFO_EXTENSION) !== 'shp') {
          continue;
        }
        $shapefile_name = $zip->getNameIndex($i);
        break;
      }

      if (!$shapefile_name) {
        throw new \Exception("Could not find SHP in ZIP");
      }

      $zip->extractTo($temp_directory);

      $zip->close();

      $shapefile_path = $temp_directory . '/' . $shapefile_name;
    }

    $this->shapefile = new ShapefileReader($shapefile_path, [
      Shapefile::OPTION_DBF_IGNORED_FIELDS => ['BRK_GROUP'],
    ]);

    $tempstore->set($url, $shapefile_path);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    if (isset($this->shapefile)) {
      $this->shapefile->rewind();
    }
    parent::rewind();
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow(): void {
    /**
     * @var \Shapefile\Geometry\Geometry | false $record
     */
    $record = $this->shapefile->fetchRecord();

    if ($record === FALSE) {
      $this->currentItem = NULL;
      return;
    }

    $this->currentItem = [];
    foreach ($record->getDataArray() as $key => $data) {
      $this->currentItem[$key] = $data;
    }
    $this->currentItem['geojson'] = $record->getGeoJSON();
  }

}
