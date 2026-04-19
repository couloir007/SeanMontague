<?php

/**
 * @file
 * ireland-import.php — Drush php:script
 *
 * Imports Ireland trip KMZ data into Drupal:
 *   1. Sites folders → geo_entity:poi (both KMZ files, deduped by label)
 *   2. "Lodging Full Trip" folder → geo_entity:destination
 *   3. Day folders with LineString → public://geoshape/ireland-*.geojson
 *
 * Run: lando drush php:script scripts/ireland-import.php
 *
 * KMZ files must be present in scripts/data/ (copied from Downloads).
 */

declare(strict_types=1);

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;

// ---------------------------------------------------------------------------
// KMZ source files (resolved relative to project root inside Lando).
// DRUPAL_ROOT = /app/public_html; project root = /app.
// ---------------------------------------------------------------------------
$project_root = dirname(DRUPAL_ROOT);
$kmz_files = [
  $project_root . '/scripts/data/ireland-day1-5.kmz',
  $project_root . '/scripts/data/ireland-day6-7.kmz',
];

// KML namespace used by Google Maps exports.
const KML_NS = 'http://www.opengis.net/kml/2.2';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Opens a KMZ (zip) file and returns the doc.kml contents.
 */
function ireland_kmz_read(string $path): ?string {
  $zip = new ZipArchive();
  if ($zip->open($path) !== TRUE) {
    return NULL;
  }
  $content = $zip->getFromName('doc.kml');
  $zip->close();
  return $content ?: NULL;
}

/**
 * Parses a KML string into a DOMXPath instance with the KML namespace bound.
 */
function ireland_kmz_xpath(string $kml): DOMXPath {
  $dom = new DOMDocument();
  $dom->loadXML($kml);
  $xpath = new DOMXPath($dom);
  $xpath->registerNamespace('kml', KML_NS);
  return $xpath;
}

/**
 * Extracts the text content of the first child kml:name element.
 */
function ireland_xpath_name(DOMXPath $xpath, DOMNode $ctx): string {
  $node = $xpath->query('kml:name', $ctx)->item(0);
  return $node ? trim($node->textContent) : '';
}

/**
 * Parses KML coordinate text ("lon,lat,ele lon,lat,ele ...") into GeoJSON
 * coordinate arrays [[lon, lat], ...].  Elevation is dropped (Google Maps
 * exports all Z as 0).
 */
function ireland_parse_kml_coords(string $raw): array {
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
 * Handles UTF-8 characters (ó → o, etc.) via iconv transliteration.
 */
function ireland_slug(string $name): string {
  $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
  $lower = strtolower($ascii);
  $clean = preg_replace('/[^a-z0-9\s-]/', '', $lower);
  $slug  = preg_replace('/[\s-]+/', '-', trim($clean));
  return 'ireland-' . $slug;
}

/**
 * Detects route type from the folder name.
 *   "Walking" or "Inis Mór" → walking
 *   "McGillicuddy Reeks"    → hiking
 *   anything else           → driving
 */
function ireland_route_type(string $folder_name): string {
  if (str_contains($folder_name, 'Walking') || str_contains($folder_name, 'Inis Mór')) {
    return 'walking';
  }
  if (str_contains($folder_name, 'McGillicuddy Reeks')) {
    return 'hiking';
  }
  return 'driving';
}

/**
 * Builds a GeoJSON FeatureCollection with a single LineString feature.
 */
function ireland_build_geojson(string $name, array $coords, string $route_type): string {
  $fc = [
    'type' => 'FeatureCollection',
    'features' => [
      [
        'type' => 'Feature',
        'geometry' => [
          'type' => 'LineString',
          'coordinates' => $coords,
        ],
        'properties' => [
          'name' => $name,
          'route_type' => $route_type,
        ],
      ],
    ],
  ];
  return json_encode($fc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Returns TRUE if a geo_entity with the given bundle and label already exists.
 */
function ireland_entity_exists(string $bundle, string $label): bool {
  $ids = \Drupal::entityQuery('geo_entity')
    ->condition('bundle', $bundle)
    ->condition('label', $label)
    ->accessCheck(FALSE)
    ->execute();
  return !empty($ids);
}

/**
 * Creates and saves a geo_entity with the given bundle, label, and coordinates.
 */
function ireland_create_entity(string $bundle, string $label, float $lon, float $lat): void {
  $storage = \Drupal::entityTypeManager()->getStorage('geo_entity');
  $values = [
    'bundle'     => $bundle,
    'label'      => $label,
    'schema_geo' => ['value' => sprintf('POINT (%F %F)', $lon, $lat)],
    'status'     => 1,
  ];
  // POIs carry a show-on-map flag; enable it for imported sites.
  if ($bundle === 'poi') {
    $values['field_show_on_map'] = 1;
  }
  $entity = $storage->create($values);
  $entity->save();
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$stats = [
  'poi'     => ['created' => 0, 'skipped' => 0, 'rows' => []],
  'dest'    => ['created' => 0, 'skipped' => 0, 'rows' => []],
  'geojson' => [],
];

/** @var FileSystemInterface $fs */
$fs = \Drupal::service('file_system');

// Ensure geoshape directory exists.
$geoshape_uri = 'public://geoshape';
$fs->prepareDirectory($geoshape_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
$geoshape_real = $fs->realpath($geoshape_uri);

foreach ($kmz_files as $kmz_path) {
  echo "\nProcessing: " . basename($kmz_path) . "\n";
  echo str_repeat('-', 50) . "\n";

  $kml = ireland_kmz_read($kmz_path);
  if ($kml === NULL) {
    echo "  ERROR: Cannot read KMZ file.\n";
    continue;
  }

  $xpath  = ireland_kmz_xpath($kml);
  $dom    = $xpath->document;

  // Select all direct Folder children of the Document element.
  $folders = $xpath->query('/kml:kml/kml:Document/kml:Folder');
  if ($folders->length === 0) {
    // Fallback: some exports omit Document wrapper.
    $folders = $xpath->query('//kml:Folder[not(parent::kml:Folder)]');
  }

  foreach ($folders as $folder) {
    $folder_name = ireland_xpath_name($xpath, $folder);

    // ------------------------------------------------------------------
    // 1. Sites → geo_entity:poi
    // ------------------------------------------------------------------
    if ($folder_name === 'Sites') {
      $placemarks = $xpath->query('kml:Placemark', $folder);
      foreach ($placemarks as $pm) {
        $pm_name = ireland_xpath_name($xpath, $pm);
        if ($pm_name === '') {
          continue;
        }
        $point = $xpath->query('kml:Point/kml:coordinates', $pm)->item(0);
        if (!$point) {
          continue;
        }
        $parts = explode(',', trim($point->textContent));
        if (count($parts) < 2) {
          continue;
        }
        $lon = (float) $parts[0];
        $lat = (float) $parts[1];

        if (ireland_entity_exists('poi', $pm_name)) {
          $stats['poi']['skipped']++;
          $stats['poi']['rows'][] = ['label' => $pm_name, 'status' => 'SKIP'];
          echo "  [poi] SKIP  $pm_name\n";
        }
        else {
          ireland_create_entity('poi', $pm_name, $lon, $lat);
          $stats['poi']['created']++;
          $stats['poi']['rows'][] = ['label' => $pm_name, 'status' => 'CREATE'];
          echo "  [poi] CREATE $pm_name\n";
        }
      }
    }

    // ------------------------------------------------------------------
    // 2. Lodging Full Trip → geo_entity:destination
    // ------------------------------------------------------------------
    elseif ($folder_name === 'Lodging Full Trip') {
      $placemarks = $xpath->query('kml:Placemark', $folder);
      foreach ($placemarks as $pm) {
        $pm_name = ireland_xpath_name($xpath, $pm);
        if ($pm_name === '') {
          continue;
        }
        $point = $xpath->query('kml:Point/kml:coordinates', $pm)->item(0);
        if (!$point) {
          continue;
        }
        $parts = explode(',', trim($point->textContent));
        if (count($parts) < 2) {
          continue;
        }
        $lon = (float) $parts[0];
        $lat = (float) $parts[1];

        if (ireland_entity_exists('destination', $pm_name)) {
          $stats['dest']['skipped']++;
          $stats['dest']['rows'][] = ['label' => $pm_name, 'status' => 'SKIP'];
          echo "  [dest] SKIP  $pm_name\n";
        }
        else {
          ireland_create_entity('destination', $pm_name, $lon, $lat);
          $stats['dest']['created']++;
          $stats['dest']['rows'][] = ['label' => $pm_name, 'status' => 'CREATE'];
          echo "  [dest] CREATE $pm_name\n";
        }
      }
    }

    // ------------------------------------------------------------------
    // 3. Day N folders → GeoJSON LineString file
    // ------------------------------------------------------------------
    elseif (preg_match('/^Day \d+/i', $folder_name)) {
      // Find the first Placemark containing a LineString — that's the route.
      $placemarks = $xpath->query('kml:Placemark', $folder);
      foreach ($placemarks as $pm) {
        $coords_node = $xpath->query('kml:LineString/kml:coordinates', $pm)->item(0);
        if (!$coords_node) {
          continue;
        }

        $pm_name    = ireland_xpath_name($xpath, $pm) ?: $folder_name;
        $coords     = ireland_parse_kml_coords($coords_node->textContent);
        if (count($coords) < 2) {
          echo "  [route] SKIP $folder_name — too few coordinates\n";
          break;
        }

        $route_type = ireland_route_type($folder_name);
        $slug       = ireland_slug($folder_name);
        $filename   = $slug . '.geojson';
        $dest_path  = $geoshape_real . '/' . $filename;
        $geojson    = ireland_build_geojson($pm_name, $coords, $route_type);

        file_put_contents($dest_path, $geojson);

        $stats['geojson'][] = [
          'file'       => $filename,
          'route_type' => $route_type,
          'points'     => count($coords),
        ];
        echo sprintf("  [route] WRITE  %-52s  %-8s  %d pts\n", $filename, $route_type, count($coords));

        // Only one route LineString per folder.
        break;
      }
    }
  }
}

// ---------------------------------------------------------------------------
// Summary table
// ---------------------------------------------------------------------------

echo "\n";
echo str_repeat('═', 72) . "\n";
echo "  IMPORT SUMMARY\n";
echo str_repeat('═', 72) . "\n";

printf("  %-12s  created: %2d   skipped: %2d\n",
  'POIs',
  $stats['poi']['created'],
  $stats['poi']['skipped'],
);
printf("  %-12s  created: %2d   skipped: %2d\n",
  'Destinations',
  $stats['dest']['created'],
  $stats['dest']['skipped'],
);

echo "\n  GeoJSON files written to public://geoshape/\n\n";
printf("  %-52s  %-8s  %s\n", 'File', 'Type', 'Points');
echo '  ' . str_repeat('─', 68) . "\n";
foreach ($stats['geojson'] as $g) {
  printf("  %-52s  %-8s  %d\n", $g['file'], $g['route_type'], $g['points']);
}
echo str_repeat('═', 72) . "\n\n";
