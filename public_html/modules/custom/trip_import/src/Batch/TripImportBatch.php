<?php

namespace Drupal\trip_import\Batch;

use Drupal\Component\Utility\Html;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;

/**
 * Static batch callbacks for the Trip KMZ importer.
 *
 * Folder detection:
 *   'Points of Interest' or 'Sites'  → pois
 *   'Destinations'                   → destinations
 *   Folder name starts with 'Lodging'→ lodging
 *   /^Day \d+/i                      → day route folder
 *
 * Route type detection (from LineString placemark name, case-insensitive):
 *   contains 'walk'                                           → walking
 *   contains 'inis','mór','mor','aran','cycl','bicycl','bike' → cycling
 *   contains 'reeks','aonghasa','mountain','hike','meenabool' → hiking
 *   everything else (driving, bus, city-to-city)              → driving
 *
 * Duplicate detection: coordinate query ±0.001° — all geo entity bundles.
 */
class TripImportBatch {

  const KML_NS = 'http://www.opengis.net/kml/2.2';

  // ---------------------------------------------------------------------------
  // Public: KMZ parsing
  // ---------------------------------------------------------------------------

  /**
   * Parses a KMZ file into structured arrays for batch operations.
   *
   * @return array|null
   *   Keys: pois, destinations, lodging, days. NULL on parse failure.
   */
  public static function parseKmz(string $real_path): ?array {
    $zip = new \ZipArchive();
    if ($zip->open($real_path) !== TRUE) {
      return NULL;
    }
    $kml = $zip->getFromName('doc.kml');
    $zip->close();
    if (!$kml) {
      return NULL;
    }

    $dom = new \DOMDocument();
    if (!@$dom->loadXML($kml)) {
      return NULL;
    }
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('kml', self::KML_NS);

    $result = [
      'pois'         => [],
      'destinations' => [],
      'lodging'      => [],
      'days'         => [],
    ];

    $folders = $xpath->query('/kml:kml/kml:Document/kml:Folder');
    if ($folders->length === 0) {
      $folders = $xpath->query('//kml:Folder[not(parent::kml:Folder)]');
    }

    foreach ($folders as $folder) {
      $folder_name = self::xpathName($xpath, $folder);

      // ── Points of Interest ────────────────────────────────────────────────
      if (in_array($folder_name, ['Points of Interest', 'Sites'], TRUE)) {
        foreach ($xpath->query('kml:Placemark', $folder) as $pm) {
          $item = self::parsePoint($xpath, $pm);
          if (!$item) {
            continue;
          }
          // Extract day number from raw label before cleaning ("Day 4: Place Name")
          if (preg_match('/^Day\s+(\d+)/i', $item['label'], $dm)) {
            $item['day'] = (int) $dm[1];
          }
          $item['label'] = self::cleanLabel($item['label']); // strip 'Day N: '
          $result['pois'][] = $item;
        }
      }

      // ── Destinations ──────────────────────────────────────────────────────
      elseif ($folder_name === 'Destinations') {
        foreach ($xpath->query('kml:Placemark', $folder) as $pm) {
          $item = self::parsePoint($xpath, $pm);
          if (!$item) {
            continue;
          }
          // Extract day numbers from raw label ("Day 5: City" or "Day 5, Day 6: City")
          preg_match_all('/Day\s+(\d+)/i', $item['label'], $dm);
          $item['days'] = !empty($dm[1]) ? array_map('intval', $dm[1]) : [];
          $item['label'] = self::cleanLabel($item['label']); // strip 'Day N: '
          $result['destinations'][] = $item;
        }
      }

      // ── Lodging ───────────────────────────────────────────────────────────
      elseif (stripos($folder_name, 'Lodging') === 0) {
        foreach ($xpath->query('kml:Placemark', $folder) as $pm) {
          $item = self::parsePoint($xpath, $pm);
          if (!$item) {
            continue;
          }
          $raw_name = $item['label'];
          $item['nights'] = self::nightsFromLabel($raw_name, $item['desc'] ?? '');
          $item['label']  = self::cleanLabel($raw_name);
          // Extract first night number from "Night 5: Hotel" or "Night 5, Night 6: Hotel"
          if (preg_match('/Night\s+(\d+)/i', $raw_name, $nm)) {
            $item['night'] = (int) $nm[1];
          }
          unset($item['desc']);
          $result['lodging'][] = $item;
        }
      }

      // ── Day route folders ─────────────────────────────────────────────────
      elseif (preg_match('/^Day\s+(\d+)/i', $folder_name, $dm)) {
        $day = (int) $dm[1];
        if (!isset($result['days'][$day])) {
          $result['days'][$day] = ['routes' => [], 'pois' => []];
        }

        $route_found = FALSE;
        foreach ($xpath->query('kml:Placemark', $folder) as $pm) {
          $coords_node = $xpath->query('kml:LineString/kml:coordinates', $pm)->item(0);

          if ($coords_node && !$route_found) {
            $pm_name = self::xpathName($xpath, $pm) ?: $folder_name;
            $coords  = self::parseKmlCoords($coords_node->textContent);
            if (count($coords) >= 2) {
              $result['days'][$day]['routes'][] = [
                'name'       => $pm_name,
                'slug'       => self::slug($folder_name),
                'coords'     => $coords,
                'route_type' => self::routeType($pm_name),
              ];
              $route_found = TRUE;
            }
          }
          elseif ($xpath->query('kml:Point', $pm)->length > 0) {
            $pm_name = self::xpathName($xpath, $pm);
            if ($pm_name) {
              $result['days'][$day]['pois'][] = self::cleanLabel($pm_name);
            }
          }
        }
      }
    }

    return $result;
  }

  // ---------------------------------------------------------------------------
  // Public: batch operation callbacks
  // ---------------------------------------------------------------------------

  /**
   * Batch op: create geo_entity:poi.
   */
  public static function importPoi(array $item, array &$context): void {
    $label = $item['label'];
    if (self::entityExistsByCoords('poi', $item['lat'], $item['lon'])) {
      $context['results']['poi_skipped'] = ($context['results']['poi_skipped'] ?? 0) + 1;
      $context['results']['rows'][] = ['POI', $label, 'SKIPPED'];
    }
    else {
      $extra = ['field_show_on_map' => 1];
      if (isset($item['day'])) {
        $extra['field_day'] = (int) $item['day'];
      }
      self::createGeoEntity('poi', $label, $item['lon'], $item['lat'], $item['url'] ?? NULL, $extra);
      $context['results']['poi_created'] = ($context['results']['poi_created'] ?? 0) + 1;
      $context['results']['rows'][] = ['POI', $label, 'CREATED'];
    }
    $context['message'] = t('Importing POI: @label', ['@label' => $label]);
  }

  /**
   * Batch op: create node:tourist_destination.
   *
   * geo_entity:destination is retired. Destinations are now content nodes
   * with a URL, editorial body, and full Schema.org TouristDestination output.
   * Duplicate detection uses a title match rather than coordinate proximity
   * since node storage does not support the BETWEEN geo query.
   */
  public static function importDestination(array $item, array &$context): void {
    $label = $item['label'];

    // Duplicate detection — skip if a tourist_destination node with this
    // title already exists.
    $existing = \Drupal::entityQuery('node')
      ->condition('type', 'tourist_destination')
      ->condition('title', $label)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($existing)) {
      $context['results']['dest_skipped'] = ($context['results']['dest_skipped'] ?? 0) + 1;
      $context['results']['rows'][] = ['Destination', $label, 'SKIPPED'];
      return;
    }

    $node_values = [
      'type'   => 'tourist_destination',
      'title'  => $label,
      'status' => 1,
      'schema_geo' => [
        'value' => sprintf('POINT (%F %F)', $item['lon'], $item['lat']),
      ],
    ];

    // Store first day number for later article attachment.
    if (!empty($item['days'])) {
      $node_values['field_day'] = (int) reset($item['days']);
    }

    $node = Node::create($node_values);

    if (!empty($item['url']) && $node->hasField('schema_same_as')) {
      $node->set('schema_same_as', ['uri' => $item['url']]);
    }

    $node->save();
    $nid = (int) $node->id();

    $context['results']['dest_created']     = ($context['results']['dest_created'] ?? 0) + 1;
    $context['results']['destination_ids'][] = $nid;
    $context['results']['rows'][] = ['Destination', $label, 'CREATED (nid ' . $nid . ')'];
    $context['message'] = t('Importing destination: @label', ['@label' => $label]);
  }

  /**
   * Batch op: create geo_entity:lodging.
   */
  public static function importLodging(array $item, array &$context): void {
    $label = $item['label'];
    if (self::entityExistsByCoords('lodging', $item['lat'], $item['lon'])) {
      $context['results']['lodging_skipped'] = ($context['results']['lodging_skipped'] ?? 0) + 1;
      $context['results']['rows'][] = ['Lodging', $label, 'SKIPPED'];
    }
    else {
      $extra = [];
      if (isset($item['nights'])) {
        $extra['field_nights'] = (int) $item['nights'];
      }
      if (isset($item['night'])) {
        $extra['field_day'] = (int) $item['night'];
      }
      self::createGeoEntity('lodging', $label, $item['lon'], $item['lat'], $item['url'] ?? NULL, $extra);
      $context['results']['lodging_created'] = ($context['results']['lodging_created'] ?? 0) + 1;
      $context['results']['rows'][] = ['Lodging', $label . ' (' . ($item['nights'] ?? 1) . ' night(s))', 'CREATED'];
    }
    $context['message'] = t('Importing lodging: @label', ['@label' => $label]);
  }

  /**
   * Batch op: create GeoJSON files, media entities, and one Article node per day.
   */
  public static function createDayArticle(int $day, array $routes, string $trip_start_date, array &$context): void {
    // Sort by type priority: driving > hiking > cycling > walking.
    $priority = ['driving' => 4, 'hiking' => 3, 'cycling' => 2, 'walking' => 1];
    usort($routes, fn($a, $b) =>
      ($priority[$b['route_type']] ?? 0) - ($priority[$a['route_type']] ?? 0)
    );
    $primary = $routes[0];

    $title  = self::cleanLabel($primary['name']);
    $offset = $day - 1;
    $date   = date('Y-m-d\TH:i:s', strtotime($trip_start_date . " +{$offset} days"));

    // Prepare geoshape directory.
    /** @var FileSystemInterface $fs */
    $fs      = \Drupal::service('file_system');
    $dir_uri = 'public://geoshape';
    $fs->prepareDirectory($dir_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    /** @var \Drupal\file\FileRepositoryInterface $file_repo */
    $file_repo = \Drupal::service('file.repository');

    $primary_media_id = NULL;

    foreach ($routes as $i => $route) {
      $geojson  = self::buildGeoJson($route['name'], $route['coords'], $route['route_type']);
      $file_uri = 'public://geoshape/' . $route['slug'] . '.geojson';

      $file = $file_repo->writeData($geojson, $file_uri, FileExists::Replace);
      $file->setPermanent();
      $file->save();

      $media = Media::create([
        'bundle'            => 'data_download',
        'name'              => $route['slug'] . '.geojson',
        'field_media_file'  => ['target_id' => $file->id()],
        'status'            => 1,
      ]);
      $media->save();

      if ($i === 0) {
        $primary_media_id = (int) $media->id();
      }

      $context['results']['routes_written'] = ($context['results']['routes_written'] ?? 0) + 1;
      $context['results']['rows'][] = [
        'Route',
        sprintf('%s (%d pts, %s)', $route['slug'] . '.geojson', count($route['coords']), $route['route_type']),
        'WRITTEN',
      ];
    }

    $node_values = [
      'type'                 => 'article',
      'title'                => $title,
      'status'               => 1,
      'schema_date_published' => $date,
    ];
    if ($primary_media_id) {
      $node_values['schema_geoshape'] = ['target_id' => $primary_media_id];
    }

    $node = Node::create($node_values);

    // Attach geo entities for this day by querying field_day.
    // Fields are plain entity_reference so ['target_id' => $id] is sufficient.
    $eq = \Drupal::entityQuery('geo_entity')->accessCheck(FALSE);

    $poi_ids = (clone $eq)
      ->condition('bundle', 'poi')
      ->condition('field_day', $day)
      ->execute();

    $node->set('schema_poi', []);
    if (!empty($poi_ids) && $node->hasField('schema_poi')) {
      $node->set('schema_poi',
        array_map(fn($id) => ['target_id' => $id], array_values($poi_ids))
      );
    }

    $node->set('schema_destination', []);
    if ($node->hasField('schema_destination')) {
      // schema_destination now targets node:tourist_destination — query nodes,
      // not geo_entity. Destinations carry field_day set during import.
      $dest_ids = \Drupal::entityQuery('node')
        ->condition('type', 'tourist_destination')
        ->condition('field_day', $day)
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($dest_ids)) {
        try {
          $node->set('schema_destination',
            array_map(fn($id) => ['target_id' => $id], array_values($dest_ids))
          );
        }
        catch (\Exception $e) {
          \Drupal::logger('trip_import')->error($e->getMessage());
        }
      }
    }

    $lodge_ids = (clone $eq)
      ->condition('bundle', 'lodging')
      ->condition('field_day', $day)
      ->execute();

    $node->set('schema_lodging', []);
    if (!empty($lodge_ids) && $node->hasField('schema_lodging')) {
      $node->set('schema_lodging',
        array_map(fn($id) => ['target_id' => $id], array_values($lodge_ids))
      );
    }

    if ($node->hasField('field_route_type')) {
      $map = self::buildRouteTypeMap();
      $tid = $map[$primary['route_type']] ?? NULL;
      if ($tid) {
        $node->set('field_route_type', ['target_id' => $tid]);
      }
    }

    $node->save();
    $nid = (int) $node->id();

    $context['results']['day_articles'][$day] = $nid;
    $context['results']['articles_created']   = ($context['results']['articles_created'] ?? 0) + 1;
    $context['results']['rows'][] = ['Article', 'Day ' . $day . ': ' . $title, 'CREATED (nid ' . $nid . ')'];
    $context['message'] = t('Creating Day @day article: @title', ['@day' => $day, '@title' => $title]);
  }

  /**
   * Batch op: create TouristTrip node (must run last — reads accumulated results).
   */
  public static function createTrip(string $title, string $start_date, array &$context): void {
    $day_articles = $context['results']['day_articles'] ?? [];
    ksort($day_articles);
    $itinerary = array_map(
      fn($nid) => ['target_id' => $nid],
      array_values($day_articles)
    );

    $dest_ids    = $context['results']['destination_ids'] ?? [];
    $destination = array_map(fn($id) => ['target_id' => $id], $dest_ids);

    $node_values = [
      'type'   => 'tourist_trip',
      'title'  => $title,
      'status' => 1,
    ];

    $node = Node::create($node_values);

    // schema_trip_dates (Smart Date, cardinality ≥ 1): item 0 = arrivalTime.
    if ($node->hasField('schema_trip_dates')) {
      $ts = strtotime($start_date);
      $node->set('schema_trip_dates', [['value' => $ts, 'end_value' => $ts]]);
    }

    if ($node->hasField('schema_date_published')) {
      $node->set('schema_date_published', $start_date); // 'Y-m-d' string
    }

    if ($node->hasField('schema_itinerary') && !empty($itinerary)) {
      $node->set('schema_itinerary', $itinerary);
    }

    if ($node->hasField('schema_destination') && !empty($destination)) {
      $node->set('schema_destination', $destination);
    }

    $node->save();
    $nid = (int) $node->id();

    $context['results']['trip_created'] = 1;
    $context['results']['rows'][] = ['TouristTrip', $title . ' (nid ' . $nid . ')', 'CREATED'];
    $context['message'] = t('Creating TouristTrip: @title', ['@title' => $title]);
  }

  /**
   * Batch finished callback — messenger summary + HTML results table.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();

    if (!$success) {
      $messenger->addError(t('Import encountered errors.'));
      return;
    }

    $messenger->addStatus(t(
      'Import complete — @poi POIs, @dest destinations, @lodge lodging, @routes routes, @art articles, @trip trips.',
      [
        '@poi'    => ($results['poi_created']      ?? 0) . ' created / ' . ($results['poi_skipped']      ?? 0) . ' skipped',
        '@dest'   => ($results['dest_created']     ?? 0) . ' created / ' . ($results['dest_skipped']     ?? 0) . ' skipped',
        '@lodge'  => ($results['lodging_created']  ?? 0) . ' created / ' . ($results['lodging_skipped']  ?? 0) . ' skipped',
        '@routes' => $results['routes_written']    ?? 0,
        '@art'    => $results['articles_created']  ?? 0,
        '@trip'   => $results['trip_created']      ?? 0,
      ]
    ));

    if (!empty($results['rows'])) {
      $html = '<table style="border-collapse:collapse;width:100%">'
        . '<thead><tr>'
        . '<th style="text-align:left;padding:4px 8px;border-bottom:2px solid #ccc">Type</th>'
        . '<th style="text-align:left;padding:4px 8px;border-bottom:2px solid #ccc">Label / File</th>'
        . '<th style="text-align:left;padding:4px 8px;border-bottom:2px solid #ccc">Status</th>'
        . '</tr></thead><tbody>';
      foreach ($results['rows'] as $row) {
        $html .= '<tr>'
          . '<td style="padding:3px 8px">' . Html::escape($row[0]) . '</td>'
          . '<td style="padding:3px 8px">' . Html::escape($row[1]) . '</td>'
          . '<td style="padding:3px 8px">' . Html::escape($row[2]) . '</td>'
          . '</tr>';
      }
      $html .= '</tbody></table>';
      $messenger->addStatus(Markup::create($html));
    }
  }

  // ---------------------------------------------------------------------------
  // Private helpers
  // ---------------------------------------------------------------------------

  private static function xpathName(\DOMXPath $xpath, \DOMNode $ctx): string {
    $node = $xpath->query('kml:name', $ctx)->item(0);
    return $node ? trim($node->textContent) : '';
  }

  /**
   * Extracts label, lon, lat, url, and desc (raw description text) from a
   * KML Point placemark. Returns NULL if name or coordinates are absent.
   */
  private static function parsePoint(\DOMXPath $xpath, \DOMNode $pm): ?array {
    $name  = self::xpathName($xpath, $pm);
    $point = $xpath->query('kml:Point/kml:coordinates', $pm)->item(0);
    if (!$name || !$point) {
      return NULL;
    }
    $parts = explode(',', trim($point->textContent));
    if (count($parts) < 2) {
      return NULL;
    }
    $desc_node = $xpath->query('kml:description', $pm)->item(0);
    $desc      = $desc_node ? trim($desc_node->textContent) : '';
    return [
      'label' => $name,
      'lon'   => (float) $parts[0],
      'lat'   => (float) $parts[1],
      'url'   => self::extractUrl($desc),
      'desc'  => $desc,
    ];
  }

  /**
   * Parses KML coordinate text ("lon,lat,ele lon,lat,ele …") into GeoJSON
   * coordinate pairs [[lon, lat], …]. Elevation is dropped (stored as 0).
   */
  private static function parseKmlCoords(string $raw): array {
    $coords = [];
    foreach (preg_split('/\s+/', trim($raw)) as $token) {
      if ($token === '') {
        continue;
      }
      $parts = explode(',', $token);
      if (count($parts) >= 2) {
        $coords[] = [(float) $parts[0], (float) $parts[1]];
      }
    }
    return $coords;
  }

  /**
   * Extracts a usable URL from a KML description string.
   * Skips empty content and Google MyMaps CDN image URLs.
   */
  private static function extractUrl(string $desc): ?string {
    if (empty(trim($desc))) {
      return NULL;
    }
    // Skip descriptions that reference Google MyMaps hosted images.
    if (strpos($desc, 'mymaps.usercontent.google.com') !== FALSE) {
      return NULL;
    }
    $plain = trim(strip_tags($desc));
    if (filter_var($plain, FILTER_VALIDATE_URL)) {
      return $plain;
    }
    if (preg_match('/https?:\/\/[^\s<>"\']+/', $plain, $m)) {
      return rtrim($m[0], '.,;)');
    }
    return NULL;
  }

  /**
   * Strips leading day/night number prefixes from placemark names.
   *   'Day 1: Foo'         → 'Foo'
   *   'Night 5, Night 6: Bar' → 'Bar'
   * Regex per spec: /^(Day|Night)\s+[\d,\s]+[;:]\s* /iu
   */
  private static function cleanLabel(?string $label): string {
    if ($label === NULL) {
      return '';
    }
    return trim(preg_replace('/^(Day|Night)\s+[\d,\s]+[;:]\s*/iu', '', $label));
  }

  /**
   * Determines number of nights from a placemark name and/or description.
   *
   * Resolution order:
   *   1. 'Night N, Night M:' in name  → M − N + 1
   *   2. 'N nights' in description    → N
   *   3. Default                      → 1
   */
  private static function nightsFromLabel(string $name, string $desc): int {
    if (preg_match_all('/Night\s+(\d+)/i', $name, $m) && !empty($m[1])) {
      $nums = array_map('intval', $m[1]);
      return max($nums) - min($nums) + 1;
    }
    if ($desc && preg_match('/(\d+)\s+nights?/i', $desc, $m)) {
      return (int) $m[1];
    }
    return 1;
  }

  /**
   * Converts a name into a lowercase URL-safe slug.
   * Handles UTF-8 characters via iconv transliteration.
   */
  private static function slug(string $name): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    $lower = strtolower($ascii);
    $clean = preg_replace('/[^a-z0-9\s-]/', '', $lower);
    return preg_replace('/[\s-]+/', '-', trim($clean));
  }

  /**
   * Detects route type from the LineString placemark name.
   *
   *   walking:  contains 'walk'
   *   cycling:  contains 'inis','mór','mor','aran','cycl','bicycl','bike'
   *   hiking:   contains 'reeks','aonghasa','mountain','hike','meenabool'
   *   driving:  everything else
   */
  private static function routeType(string $name): string {
    $n = mb_strtolower($name);
    if (str_contains($n, 'walk')) {
      return 'walking';
    }
    foreach (['inis', 'mór', 'mor', 'aran', 'cycl', 'bicycl', 'bike'] as $kw) {
      if (str_contains($n, $kw)) {
        return 'cycling';
      }
    }
    foreach (['reeks', 'aonghasa', 'mountain', 'hike', 'hiking', 'meenabool'] as $kw) {
      if (str_contains($n, $kw)) {
        return 'hiking';
      }
    }
    return 'driving';
  }

  /**
   * Builds a GeoJSON FeatureCollection with a single LineString feature.
   */
  private static function buildGeoJson(string $name, array $coords, string $route_type): string {
    return json_encode(
      [
        'type'     => 'FeatureCollection',
        'features' => [
          [
            'type'     => 'Feature',
            'geometry' => ['type' => 'LineString', 'coordinates' => $coords],
            'properties' => ['name' => $name, 'route_type' => $route_type],
          ],
        ],
      ],
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
  }

  /**
   * Returns TRUE if a geo_entity of the given bundle exists within 0.001°
   * of the supplied coordinates.
   */
  private static function entityExistsByCoords(string $bundle, float $lat, float $lon): bool {
    $ids = \Drupal::entityQuery('geo_entity')
      ->condition('bundle', $bundle)
      ->condition('schema_geo.lat', [$lat - 0.001, $lat + 0.001], 'BETWEEN')
      ->condition('schema_geo.lon', [$lon - 0.001, $lon + 0.001], 'BETWEEN')
      ->accessCheck(FALSE)
      ->execute();
    return !empty($ids);
  }

  /**
   * Creates and saves a geo_entity, optionally setting schema_same_as and
   * any extra field values. Returns the new entity ID.
   */
  private static function createGeoEntity(
    string $bundle,
    string $label,
    float $lon,
    float $lat,
    ?string $url,
    array $extra = [],
  ): int {
    $storage = \Drupal::entityTypeManager()->getStorage('geo_entity');
    $values  = array_merge(
      [
        'bundle'     => $bundle,
        'label'      => $label,
        'schema_geo' => ['value' => sprintf('POINT (%F %F)', $lon, $lat)],
        'status'     => 1,
      ],
      $extra
    );
    $entity = $storage->create($values);

    if ($url && $entity->hasField('schema_same_as')) {
      $entity->set('schema_same_as', ['uri' => $url]);
    }

    $entity->save();
    return (int) $entity->id();
  }

  private static function buildRouteTypeMap(): array {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'route_type']);
    $map = [];
    foreach ($terms as $term) {
      if (!$term->get('field_key')->isEmpty()) {
        $map[$term->get('field_key')->value] = (int) $term->id();
      }
    }
    return $map;
  }

}

