<?php

namespace Drupal\trail_mapper\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\NodeInterface;

/**
 * Converts GPX and GeoJSON uploads to normalized GeoJSON with Z in feet.
 *
 * Called from trail_mapper_entity_presave() hook. Injected via service
 * container to keep the hook thin and the logic testable.
 *
 * Elevation unit assumption: all uploaded files store elevation in meters.
 * Strava, Garmin, Gaia GPS, and Komoot all export in meters. Hand-crafted
 * GeoJSON on this site also uses meters by convention.
 * If a file uses feet, pre-convert before uploading.
 */
class GeoShapeConverter {

  public function __construct(
    protected readonly FileSystemInterface $fileSystem,
    protected readonly FileRepositoryInterface $fileRepository,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Processes schema_geoshape on a node — converts and overwrites in place.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function processEntity(EntityInterface $entity): void {
    if (!$entity instanceof NodeInterface) {
      return;
    }
    if (!$entity->hasField('schema_geoshape')) {
      return;
    }

    $geoshape = $entity->get('schema_geoshape');
    if ($geoshape->isEmpty()) {
      return;
    }

    /** @var \Drupal\file\Entity\File|null $file */
    $referenced = $geoshape->referencedEntities();
    $file = !empty($referenced) ? reset($referenced) : NULL;
    if (!($file instanceof File)) {
      return;
    }

    $uri = $file->getFileUri();
    $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));

    $geojson = match ($extension) {
      'gpx'           => $this->convertGpx($uri),
      'geojson', 'json' => $this->normalizeGeoJson($uri),
      default         => NULL,
    };

    if ($geojson === NULL) {
      return;
    }

    $this->overwriteGeoshape($entity, $file, $geojson);
  }

  /**
   * Converts a GPX file to GeoJSON FeatureCollection with Z in feet.
   *
   * @param string $uri
   *   Drupal stream wrapper URI to the GPX file.
   *
   * @return string|null
   *   JSON-encoded GeoJSON string, or NULL on failure.
   */
  protected function convertGpx(string $uri): ?string {
    $path = $this->fileSystem->realpath($uri);
    if (!$path || !file_exists($path)) {
      $this->loggerFactory->get('trail_mapper')
        ->error('GPX file not found: @uri', ['@uri' => $uri]);
      return NULL;
    }

    libxml_use_internal_errors(TRUE);
    $gpx = simplexml_load_file($path);
    if (!$gpx) {
      $this->loggerFactory->get('trail_mapper')
        ->error('Failed to parse GPX: @uri', ['@uri' => $uri]);
      return NULL;
    }

    $features = [];

    // Support both track (<trk>/<trkseg>/<trkpt>) and
    // route (<rte>/<rtept>) GPX formats.
    foreach ($gpx->trk as $trk) {
      $name = (string) $trk->name ?: 'Track';
      foreach ($trk->trkseg as $seg) {
        $coordinates = $this->extractCoordinates($seg->trkpt);
        if (empty($coordinates)) {
          continue;
        }
        $features[] = $this->makeFeature($coordinates, $name, 'gpx');
      }
    }

    foreach ($gpx->rte as $rte) {
      $name = (string) $rte->name ?: 'Route';
      $coordinates = $this->extractCoordinates($rte->rtept);
      if (empty($coordinates)) {
        continue;
      }
      $features[] = $this->makeFeature($coordinates, $name, 'gpx');
    }

    if (empty($features)) {
      $this->loggerFactory->get('trail_mapper')
        ->warning('No track segments found in GPX: @uri', ['@uri' => $uri]);
      return NULL;
    }

    try {
      return json_encode([
        'type' => 'FeatureCollection',
        'features' => $features,
      ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      $this->loggerFactory->get('trail_mapper')
        ->error('Failed to encode GPX as GeoJSON: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Normalizes a GeoJSON file, converting Z values from meters to feet.
   *
   * @param string $uri
   *   Drupal stream wrapper URI to the GeoJSON file.
   *
   * @return string|null
   *   JSON-encoded GeoJSON string with Z in feet, or NULL on failure.
   */
  protected function normalizeGeoJson(string $uri): ?string {
    $path = $this->fileSystem->realpath($uri);
    if (!$path || !file_exists($path)) {
      $this->loggerFactory->get('trail_mapper')
        ->error('GeoJSON file not found: @uri', ['@uri' => $uri]);
      return NULL;
    }

    $content = file_get_contents($path);
    if (!$content) {
      return NULL;
    }

    try {
      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      $this->loggerFactory->get('trail_mapper')
        ->error('Invalid JSON in GeoJSON file: @uri — @msg', [
          '@uri' => $uri,
          '@msg' => $e->getMessage(),
        ]);
      return NULL;
    }

    if (!isset($data['type'])) {
      $this->loggerFactory->get('trail_mapper')
        ->error('Invalid GeoJSON structure: @uri', ['@uri' => $uri]);
      return NULL;
    }

    if (isset($data['features'])) {
      foreach ($data['features'] as &$feature) {
        $type = $feature['geometry']['type'] ?? '';
        $coords = &$feature['geometry']['coordinates'];

        if ($type === 'LineString') {
          foreach ($coords as &$coord) {
            if (isset($coord[2])) {
              $coord[2] = round($coord[2] * 3.28084, 1);
            }
          }
          unset($coord);
        }
        elseif ($type === 'MultiLineString') {
          foreach ($coords as &$line) {
            foreach ($line as &$coord) {
              if (isset($coord[2])) {
                $coord[2] = round($coord[2] * 3.28084, 1);
              }
            }
            unset($coord);
          }
          unset($line);
        }

        $feature['properties']['elevation_unit'] = 'feet';
        $feature['properties']['source'] = $feature['properties']['source'] ?? 'geojson';
      }
      unset($feature);
    }

    try {
      return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      $this->loggerFactory->get('trail_mapper')
        ->error('Failed to encode GeoJSON: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Overwrites schema_geoshape with normalized GeoJSON content.
   *
   * After save, schema_geoshape always contains a .geojson file.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node being saved.
   * @param \Drupal\file\Entity\File $originalFile
   *   The originally uploaded file entity.
   * @param string $geojson
   *   The normalized GeoJSON string to store.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function overwriteGeoshape(
    NodeInterface $entity,
    File $originalFile,
    string $geojson,
  ): void {
    $dir = 'public://geoshape';

    if (!$this->fileSystem->prepareDirectory(
      $dir,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    )) {
      $this->loggerFactory->get('trail_mapper')
        ->error('Could not prepare geoshape directory.');
      return;
    }

    $basename = pathinfo($originalFile->getFilename(), PATHINFO_FILENAME);
    $destination = $dir . '/' . $basename . '.geojson';

    $savedUri = $this->fileSystem->saveData(
      $geojson,
      $destination,
      FileSystemInterface::EXISTS_REPLACE
    );

    if (!$savedUri) {
      $this->loggerFactory->get('trail_mapper')
        ->error('Could not save normalized GeoJSON.');
      return;
    }

    $originalFile->setFileUri($savedUri);
    $originalFile->setFilename(basename($savedUri));
    $originalFile->setMimeType('application/geo+json');
    $originalFile->save();

    // Re-attach converted file. No $entity->save() needed — we are inside
    // hook_entity_presave so the entity saves once with this value already set.
    $entity->set('schema_geoshape', ['target_id' => $originalFile->id()]);
  }

  /**
   * Extracts [lon, lat, elevation_ft] coordinates from GPX point elements.
   *
   * @param \SimpleXMLElement $points
   *   Collection of GPX point elements (trkpt or rtept).
   *
   * @return array
   *   Array of coordinate arrays [lon, lat] or [lon, lat, ele_ft].
   */
  protected function extractCoordinates(\SimpleXMLElement $points): array {
    $coordinates = [];
    foreach ($points as $pt) {
      $lon = (float) $pt['lon'];
      $lat = (float) $pt['lat'];
      // GPX elevation in meters — convert to feet.
      $ele = isset($pt->ele)
        ? round((float) $pt->ele * 3.28084, 1)
        : NULL;
      $coordinates[] = $ele !== NULL
        ? [$lon, $lat, $ele]
        : [$lon, $lat];
    }
    return $coordinates;
  }

  /**
   * Builds a GeoJSON Feature from coordinates.
   *
   * @param array $coordinates
   *   Array of coordinate arrays.
   * @param string $name
   *   Feature name for properties.
   * @param string $source
   *   Source format (gpx, geojson).
   *
   * @return array
   *   GeoJSON Feature array.
   */
  protected function makeFeature(array $coordinates, string $name, string $source): array {
    return [
      'type' => 'Feature',
      'geometry' => [
        'type' => 'LineString',
        'coordinates' => $coordinates,
      ],
      'properties' => [
        'name' => $name,
        'source' => $source,
        'elevation_unit' => 'feet',
      ],
    ];
  }

}
