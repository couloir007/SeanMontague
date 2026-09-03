<?php

namespace Drupal\geolocation_geometry\Feeds\Parser;

use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Parser\ParserBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileReader;

/**
 * Defines a CSV feed parser.
 *
 * @FeedsParser(
 *   id = "shp",
 *   title = "Shapefile",
 *   description = @Translation("Parse Geometry Shape files."),
 * )
 */
class ShapefileParser extends ParserBase {

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fileSystem = \Drupal::service('file_system');
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state): ParserResultInterface {
    // Get sources.
    $sources = [];
    $skip_sources = [];
    foreach ($feed->getType()->getMappingSources() as $key => $info) {
      if (isset($info['type']) && $info['type'] != 'shp') {
        $skip_sources[$key] = $key;
        continue;
      }
      if (isset($info['value']) && trim(strval($info['value'])) !== '') {
        $sources[$info['value']] = $key;
      }
    }

    if (!filesize($fetcher_result->getFilePath())) {
      throw new EmptyFeedException();
    }

    $extracted_path = $this->fileSystem->getTempDirectory() . '/' . basename($fetcher_result->getFilePath()) . '-extracted-shapefile/';
    $this->fileSystem->prepareDirectory($extracted_path, FileSystemInterface::CREATE_DIRECTORY);

    $zip = new \ZipArchive();
    $res = $zip->open($this->fileSystem->realpath($fetcher_result->getFilePath()));
    if ($res === TRUE) {
      $zip->extractTo($extracted_path);
      $zip->close();
    }
    else {
      throw new \Exception(t('Could not open Shapefile ZIP.'));
    }

    try {
      $shapefiles = glob($extracted_path . '/*.shp');
      if (!$shapefiles) {
        throw new \Exception('No shapefile found.');
      }

      if (count($shapefiles) > 1) {
        throw new \Exception('Multiple shapefiles not supported.');
      }

      $shapefile = new ShapefileReader($shapefiles[0], [
        Shapefile::OPTION_DBF_IGNORED_FIELDS => ['BRK_GROUP'],
      ]);
    }
    catch (ShapefileException $e) {
      throw new \Exception(t('Failed %message', ['%message' => $e->getMessage()]));
    }

    $result = new ParserResult();

    // Debugging helping variable.
    $sample_data = [];
    $sample_set = FALSE;

    // @phpstan-ignore-next-line Record might be empty.
    while ($record = $shapefile->fetchRecord()) {
      if ($record->isDeleted()) {
        continue;
      }

      $item = new DynamicItem();

      $item->set('geojson', $record->getGeoJSON());

      if (!$sample_set) {
        $sample_data[] = 'GeoJSON: ' . substr($record->getGeoJSON(), 0, 300);
      }

      foreach ($record->getDataArray() as $key => $data) {
        if (isset($skip_sources[$key])) {
          // Skip custom sources that are not of type "csv".
          continue;
        }
        // Pick machine name of source, if one is found.
        if (isset($sources[$key])) {
          $key = $sources[$key];
        }
        $item->set($key, $data);

        if (!$sample_set) {
          $sample_data[] = $key . ': ' . substr($data, 0, 300);
        }
      }

      $result->addItem($item);

      $sample_set = TRUE;
    }

    // @phpstan-ignore-next-line What?
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources(): array {
    return [
      'geojson' => [
        'label' => $this->t('GeoJSON'),
        'description' => $this->t('GeoJSON from Shapefile record.'),
        'suggestions' => [
          'types' => ['field_item:geolocation_geometry' => []],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return ['shp'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFeedConfiguration(): array {
    return [
      'delimiter' => $this->configuration['delimiter'],
      'no_headers' => $this->configuration['no_headers'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'delimiter' => ',',
      'no_headers' => 0,
      'line_limit' => 100,
    ];
  }

}
