<?php

namespace Drupal\ireland_import\Batch;

use Drupal\Component\Utility\Html;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\Markup;

/**
 * Static batch callbacks for the Ireland trip KMZ importer.
 *
 * All public methods are Drupal Batch API callbacks; private helpers are
 * shared KML/GeoJSON utilities extracted to avoid repetition.
 */
class IrelandImportBatch {

  const KML_NS = 'http://www.opengis.net/kml/2.2';

  // ---------------------------------------------------------------------------
  // Public: KMZ parsing (called from IrelandImportForm::submitForm)
  // ---------------------------------------------------------------------------

  /**
   * Parses a KMZ file into structured arrays for batch operations.
   *
   * @param string $real_path
   *   Absolute filesystem path to the .kmz file.
   *
   * @return array|null
   *   Associative array with keys 'pois', 'destinations', 'routes',
   *   or NULL if the file cannot be read/parsed.
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

    $result = ['pois' => [], 'destinations' => [], 'routes' => []];

    // Top-level folders only — fall back if Document wrapper is absent.
    $folders = $xpath->query('/kml:kml/kml:Document/kml:Folder');
    if ($folders->length === 0) {
      $folders = $xpath->query('//kml:Folder[not(parent::kml:Folder)]');
    }

    foreach ($folders as $folder) {
      $folder_name = self::xpathName($xpath, $folder);

      if ($folder_name === 'Sites') {
        foreach ($xpath->query('kml:Placemark', $folder) as $pm) {
          $item = self::parsePoint($xpath, $pm);
          if ($item) {
            $result['pois'][] = $item;
          }
        }
      }
      elseif ($folder_name === 'Lodging Full Trip') {
        foreach ($xpath->query('kml:Placemark', $folder) as $pm) {
          $item = self::parsePoint($xpath, $pm);
          if ($item) {
            // Strip address cruft after first comma ("Premier Inn Dublin Airport hotel, Airside…").
            $item['label'] = trim(explode(',', $item['label'])[0]);
            $result['destinations'][] = $item;
          }
        }
      }
      elseif (preg_match('/^Day \d+/i', $folder_name)) {
        foreach ($xpath->query('kml:Placemark', $folder) as $pm) {
          $coords_node = $xpath->query('kml:LineString/kml:coordinates', $pm)->item(0);
          if (!$coords_node) {
            continue;
          }
          $pm_name = self::xpathName($xpath, $pm) ?: $folder_name;
          $coords  = self::parseKmlCoords($coords_node->textContent);
          if (count($coords) < 2) {
            continue;
          }
          $result['routes'][] = [
            'folder_name' => $folder_name,
            'name'        => $pm_name,
            'coords'      => $coords,
            'slug'        => self::slug($folder_name),
            'route_type'  => self::routeType($folder_name),
          ];
          // One LineString route per Day folder.
          break;
        }
      }
    }

    return $result;
  }

  // ---------------------------------------------------------------------------
  // Public: Batch operation callbacks
  // ---------------------------------------------------------------------------

  /**
   * Batch op: create a geo_entity:poi from a parsed Sites placemark.
   */
  public static function importPoi(array $item, array &$context): void {
    $label = $item['label'];
    if (self::entityExists('poi', $label)) {
      $context['results']['poi_skipped'] = ($context['results']['poi_skipped'] ?? 0) + 1;
      $context['results']['rows'][] = ['POI', $label, 'SKIPPED'];
    }
    else {
      self::createEntity('poi', $label, $item['lon'], $item['lat'], TRUE);
      $context['results']['poi_created'] = ($context['results']['poi_created'] ?? 0) + 1;
      $context['results']['rows'][] = ['POI', $label, 'CREATED'];
    }
    $context['message'] = t('Importing POI: @label', ['@label' => $label]);
  }

  /**
   * Batch op: create a geo_entity:destination from a Lodging placemark.
   */
  public static function importDestination(array $item, array &$context): void {
    $label = $item['label'];
    if (self::entityExists('destination', $label)) {
      $context['results']['dest_skipped'] = ($context['results']['dest_skipped'] ?? 0) + 1;
      $context['results']['rows'][] = ['Destination', $label, 'SKIPPED'];
    }
    else {
      self::createEntity('destination', $label, $item['lon'], $item['lat'], FALSE);
      $context['results']['dest_created'] = ($context['results']['dest_created'] ?? 0) + 1;
      $context['results']['rows'][] = ['Destination', $label, 'CREATED'];
    }
    $context['message'] = t('Importing destination: @label', ['@label' => $label]);
  }

  /**
   * Batch op: write a GeoJSON LineString file for a Day route.
   */
  public static function writeRoute(array $item, array &$context): void {
    /** @var FileSystemInterface $fs */
    $fs      = \Drupal::service('file_system');
    $dir_uri = 'public://geoshape';
    $fs->prepareDirectory($dir_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $real_dir = $fs->realpath($dir_uri);

    $filename = $item['slug'] . '.geojson';
    $geojson  = self::buildGeoJson($item['name'], $item['coords'], $item['route_type']);
    file_put_contents($real_dir . '/' . $filename, $geojson);

    $context['results']['routes_written'] = ($context['results']['routes_written'] ?? 0) + 1;
    $context['results']['rows'][] = [
      'Route',
      sprintf('%s (%d pts, %s)', $filename, count($item['coords']), $item['route_type']),
      'WRITTEN',
    ];
    $context['message'] = t('Writing route: @file', ['@file' => $filename]);
  }

  /**
   * Batch finished callback — displays a summary and results table.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();

    if (!$success) {
      $messenger->addError(t('Import encountered errors.'));
      return;
    }

    $poi_created  = $results['poi_created']    ?? 0;
    $poi_skipped  = $results['poi_skipped']    ?? 0;
    $dest_created = $results['dest_created']   ?? 0;
    $dest_skipped = $results['dest_skipped']   ?? 0;
    $routes       = $results['routes_written'] ?? 0;

    $messenger->addStatus(t(
      'Import complete — POIs: @pc created, @ps skipped | Destinations: @dc created, @ds skipped | Routes: @r written.',
      [
        '@pc' => $poi_created,
        '@ps' => $poi_skipped,
        '@dc' => $dest_created,
        '@ds' => $dest_skipped,
        '@r'  => $routes,
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
   * Extracts label + lon/lat from a KML Point placemark, or NULL if incomplete.
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
    return [
      'label' => $name,
      'lon'   => (float) $parts[0],
      'lat'   => (float) $parts[1],
    ];
  }

  /**
   * Parses KML coordinate text ("lon,lat,ele lon,lat,ele …") into GeoJSON
   * coordinate arrays [[lon, lat], …]. Elevation is dropped (exported as 0).
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
   * Converts a folder name to a URL-safe slug prefixed with "ireland-".
   */
  private static function slug(string $name): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    $lower = strtolower($ascii);
    $clean = preg_replace('/[^a-z0-9\s-]/', '', $lower);
    $slug  = preg_replace('/[\s-]+/', '-', trim($clean));
    return 'ireland-' . $slug;
  }

  /**
   * Detects route type from the Day folder name.
   *   "Walking" or "Inis" → walking
   *   "Reeks"   or "Dún"  → hiking
   *   anything else       → driving
   */
  private static function routeType(string $folder_name): string {
    if (str_contains($folder_name, 'Walking') || str_contains($folder_name, 'Inis')) {
      return 'walking';
    }
    if (str_contains($folder_name, 'Reeks') || str_contains($folder_name, 'Dún')) {
      return 'hiking';
    }
    return 'driving';
  }

  /**
   * Builds a GeoJSON FeatureCollection with a single LineString feature.
   */
  private static function buildGeoJson(string $name, array $coords, string $route_type): string {
    return json_encode(
      [
        'type' => 'FeatureCollection',
        'features' => [
          [
            'type'     => 'Feature',
            'geometry' => [
              'type'        => 'LineString',
              'coordinates' => $coords,
            ],
            'properties' => [
              'name'       => $name,
              'route_type' => $route_type,
            ],
          ],
        ],
      ],
      JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
  }

  /**
   * Returns TRUE if a geo_entity with the given bundle and label exists.
   */
  private static function entityExists(string $bundle, string $label): bool {
    $ids = \Drupal::entityQuery('geo_entity')
      ->condition('bundle', $bundle)
      ->condition('label', $label)
      ->accessCheck(FALSE)
      ->execute();
    return !empty($ids);
  }

  /**
   * Creates and saves a geo_entity with the given bundle, label, and WKT point.
   */
  private static function createEntity(string $bundle, string $label, float $lon, float $lat, bool $show_on_map): void {
    $storage = \Drupal::entityTypeManager()->getStorage('geo_entity');
    $values  = [
      'bundle'     => $bundle,
      'label'      => $label,
      'schema_geo' => ['value' => sprintf('POINT (%F %F)', $lon, $lat)],
      'status'     => 1,
    ];
    if ($show_on_map) {
      $values['field_show_on_map'] = 1;
    }
    $storage->create($values)->save();
  }

}
