<?php

namespace Drupal\trail_mapper\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\Entity\File;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\trail_mapper\Form\TrailMapperSettingsForm;

/**
 * Converts GPX and GeoJSON uploads to normalized GeoJSON with Z in meters.
 *
 * Called from trail_mapper_entity_presave() hook. Injected via service
 * container to keep the hook thin and the logic testable.
 *
 * Elevation is stored in meters in GeoJSON. Conversion to feet (or other
 * display units) is handled client-side in map.js based on user preference.
 */
class GeoShapeConverter {

  public function __construct(
    protected readonly FileSystemInterface $fileSystem,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
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

    $referenced = $geoshape->referencedEntities();
    if (empty($referenced)) {
      return;
    }

    // Convert every referenced media in place and rebuild the ordered list of
    // target IDs. Media are mutated (file + media saved in place) so their IDs
    // do not change; the field is set once at the end so a multi-value list is
    // no longer collapsed down to a single item.
    $targetIds = [];

    foreach ($referenced as $media) {
      if (!($media instanceof MediaInterface)) {
        continue;
      }
      // Preserve this media in the list regardless of conversion outcome.
      $targetIds[] = $media->id();

      /** @var \Drupal\file\Entity\File|null $file */
      $file = $media->field_media_file->entity ?? NULL;
      if (!($file instanceof File)) {
        continue;
      }

      $uri = $file->getFileUri();
      $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));

      // Route-type key from this media's field_route_type term (media wins).
      $routeKey = $this->resolveRouteKey($media);

      $geojson = match ($extension) {
        'gpx'             => $this->convertGpx($uri, $routeKey),
        'geojson', 'json' => $this->normalizeGeoJson($uri, $routeKey),
        default           => NULL,
      };

      if ($geojson === NULL) {
        continue;
      }

      $this->overwriteGeoshape($media, $file, $geojson);

      if ($extension === 'gpx' && TrailMapperSettingsForm::extractWaypointsEnabled()) {
        // $uri captured before overwriteGeoshape mutates $file — original GPX
        // is still on disk; only the file entity record is updated.
        $this->extractWaypoints($uri, $entity);
      }
    }

    // Set the field once to the full ordered list — stop collapsing to one.
    $entity->set('schema_geoshape', $targetIds);
  }

  /**
   * Converts a GPX file to GeoJSON FeatureCollection with Z in meters.
   *
   * @param string $uri
   *   Drupal stream wrapper URI to the GPX file.
   * @param string|null $routeKey
   *   Route-type key from the media's field_route_type term, baked into each
   *   feature's properties.route_type when set; NULL leaves it unset.
   *
   * @return string|null
   *   JSON-encoded GeoJSON string, or NULL on failure.
   */
  protected function convertGpx(string $uri, ?string $routeKey = NULL): ?string {
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
        $features[] = $this->makeFeature($coordinates, $name, 'gpx', $routeKey);
      }
    }

    foreach ($gpx->rte as $rte) {
      $name = (string) $rte->name ?: 'Route';
      $coordinates = $this->extractCoordinates($rte->rtept);
      if (empty($coordinates)) {
        continue;
      }
      $features[] = $this->makeFeature($coordinates, $name, 'gpx', $routeKey);
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
   * Normalizes a GeoJSON file. Z values are kept in meters; display
   * unit conversion is handled client-side.
   *
   * @param string $uri
   *   Drupal stream wrapper URI to the GeoJSON file.
   * @param string|null $routeKey
   *   Route-type key from the media's field_route_type term. When set it is
   *   baked into each feature's properties.route_type (media wins); when NULL
   *   any existing properties.route_type (e.g. from trip_import) is preserved.
   *
   * @return string|null
   *   JSON-encoded GeoJSON string with Z in meters, or NULL on failure.
   */
  protected function normalizeGeoJson(string $uri, ?string $routeKey = NULL): ?string {
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
        // Z values are stored as-is in meters. Conversion to display units
        // (feet/meters) is handled client-side in map.js.
        $feature['properties']['elevation_unit'] = 'meters';
        $feature['properties']['source'] = $feature['properties']['source'] ?? 'geojson';
        // Media route_type wins when set; otherwise leave any existing
        // route_type untouched (preserves a value baked in by trip_import).
        if ($routeKey !== NULL) {
          $feature['properties']['route_type'] = $routeKey;
        }
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
   * Overwrites a single media's file in place with normalized GeoJSON content.
   *
   * Mutates the file and media entities (both saved here). The media ID is
   * unchanged, so the caller re-points schema_geoshape to the same target.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity referencing the file.
   * @param \Drupal\file\Entity\File $originalFile
   *   The source file entity inside the media item.
   * @param string $geojson
   *   The normalized GeoJSON string to store.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function overwriteGeoshape(
    MediaInterface $media,
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

    // Update the media label to match the new filename.
    $media->setName(pathinfo($savedUri, PATHINFO_FILENAME));
    $media->save();
  }

  /**
   * Resolves the route-type key from a media's field_route_type term.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The geoshape media entity.
   *
   * @return string|null
   *   The referenced term's field_key value, or NULL if the field, the term,
   *   or the key is unset.
   */
  protected function resolveRouteKey(MediaInterface $media): ?string {
    if (!$media->hasField('field_route_type') || $media->get('field_route_type')->isEmpty()) {
      return NULL;
    }
    $term = $media->get('field_route_type')->entity;
    if (!$term || !$term->hasField('field_key') || $term->get('field_key')->isEmpty()) {
      return NULL;
    }
    return $term->get('field_key')->value ?: NULL;
  }

  /**
   * Extracts <wpt> waypoints from a GPX file and creates POI geo entities.
   *
   * Skips waypoints whose coordinates match an existing POI within 0.001°.
   * Country code is inferred from the article's schema_place if available.
   *
   * @param string $uri
   *   Stream wrapper URI to the original GPX file (before overwrite).
   * @param \Drupal\node\NodeInterface $article
   *   The article node being saved.
   */
  protected function extractWaypoints(string $uri, NodeInterface $article): void {
    $path = $this->fileSystem->realpath($uri);
    if (!$path || !file_exists($path)) {
      return;
    }

    libxml_use_internal_errors(TRUE);
    $gpx = simplexml_load_file($path);
    if (!$gpx) {
      return;
    }

    $countryCode = $this->resolveCountryCode($article);
    $created = 0;

    foreach ($gpx->wpt as $wpt) {
      $name = trim((string) $wpt->name);
      if (!$name) {
        continue;
      }
      $lat = (float) $wpt['lat'];
      $lon = (float) $wpt['lon'];
      if ($lat === 0.0 && $lon === 0.0) {
        continue;
      }

      if ($this->poiExistsNearby($lat, $lon)) {
        continue;
      }

      $this->createPoi($name, $lat, $lon, $countryCode);
      $created++;
    }

    if ($created > 0) {
      $this->loggerFactory->get('trail_mapper')->info(
        'Created @count POI(s) from GPX waypoints for node @nid.',
        ['@count' => $created, '@nid' => $article->id()],
      );
    }
  }

  /**
   * Resolves the country code from the article's referenced Place node.
   *
   * @param \Drupal\node\NodeInterface $article
   *   The article node.
   *
   * @return string|null
   *   ISO 3166-1 alpha-2 country code, or NULL if unavailable.
   */
  protected function resolveCountryCode(NodeInterface $article): ?string {
    if (!$article->hasField('schema_place') || $article->get('schema_place')->isEmpty()) {
      return NULL;
    }
    $place = $article->schema_place->entity;
    if (!$place || !$place->hasField('schema_address') || $place->get('schema_address')->isEmpty()) {
      return NULL;
    }
    return $place->schema_address->country_code ?: NULL;
  }

  /**
   * Checks whether a geo_entity:poi with matching coordinates already exists.
   *
   * @param float $lat
   *   Latitude to check.
   * @param float $lon
   *   Longitude to check.
   *
   * @return bool
   *   TRUE if a POI exists within 0.001° on both axes.
   */
  protected function poiExistsNearby(float $lat, float $lon): bool {
    $storage = $this->entityTypeManager->getStorage('geo_entity');
    $ids = $storage->getQuery()
      ->condition('bundle', 'poi')
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return FALSE;
    }

    foreach ($storage->loadMultiple($ids) as $poi) {
      if ($poi->get('schema_geo')->isEmpty()) {
        continue;
      }
      $item = $poi->get('schema_geo')->first();
      $existingLat = (float) $item->get('lat')->getValue();
      $existingLon = (float) $item->get('lon')->getValue();
      if (abs($existingLat - $lat) <= 0.001 && abs($existingLon - $lon) <= 0.001) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Creates a new geo_entity:poi from waypoint data.
   *
   * @param string $name
   *   Waypoint name — becomes the entity label.
   * @param float $lat
   *   Latitude.
   * @param float $lon
   *   Longitude.
   * @param string|null $countryCode
   *   ISO 3166-1 alpha-2 country code for schema_address, or NULL.
   */
  protected function createPoi(string $name, float $lat, float $lon, ?string $countryCode): void {
    $values = [
      'bundle'     => 'poi',
      'label'      => $name,
      'schema_geo' => ['value' => 'POINT (' . $lon . ' ' . $lat . ')'],
    ];

    if ($countryCode) {
      $values['schema_address'] = ['country_code' => $countryCode];
    }

    try {
      $this->entityTypeManager->getStorage('geo_entity')
        ->create($values)
        ->save();
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('trail_mapper')->error(
        'Failed to create POI "@name": @msg',
        ['@name' => $name, '@msg' => $e->getMessage()],
      );
    }
  }

  /**
   * Extracts [lon, lat, elevation_m] coordinates from GPX point elements.
   *
   * @param \SimpleXMLElement $points
   *   Collection of GPX point elements (trkpt or rtept).
   *
   * @return array
   *   Array of coordinate arrays [lon, lat] or [lon, lat, ele_m].
   */
  protected function extractCoordinates(\SimpleXMLElement $points): array {
    $coordinates = [];
    foreach ($points as $pt) {
      $lon = (float) $pt['lon'];
      $lat = (float) $pt['lat'];
      // GPX elevation in meters — stored as-is; conversion handled in map.js.
      $ele = isset($pt->ele)
        ? round((float) $pt->ele, 4)
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
   * @param string|null $routeKey
   *   Route-type key baked into properties.route_type when set; NULL omits it.
   *
   * @return array
   *   GeoJSON Feature array.
   */
  protected function makeFeature(array $coordinates, string $name, string $source, ?string $routeKey = NULL): array {
    $properties = [
      'name' => $name,
      'source' => $source,
      'elevation_unit' => 'meters',
    ];
    // Media route_type wins when set; a freshly built GPX feature has no
    // pre-existing route_type to preserve.
    if ($routeKey !== NULL) {
      $properties['route_type'] = $routeKey;
    }
    return [
      'type' => 'Feature',
      'geometry' => [
        'type' => 'LineString',
        'coordinates' => $coordinates,
      ],
      'properties' => $properties,
    ];
  }

}
