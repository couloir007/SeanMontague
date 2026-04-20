# Trip KMZ Importer — Build Steps

These steps build a generic trip importer module that works for any
Google My Maps KMZ export following the naming convention below.

Reference files for Claude Code:
- Root CLAUDE.md
- public_html/modules/custom/ireland_import/ (existing module to rewrite)
- public_html/modules/custom/gvp_schemadotorg/ (PHP pattern reference)

---

## KML Naming Convention (required for importer to work)

```
Document
├── Points of Interest     → geo_entity:poi
│   └── Day N: Place Name
├── Destinations           → geo_entity:destination
│   └── Day N: City Name
├── Lodging                → geo_entity:lodging
│   └── Night N: Hotel Name
└── Day N: Route Description   (folder per route)
    └── Day N: Route Description   (LineString placemark)
```

Rules:
- Always `Day N:` with single colon and single space
- Strip `Day N: ` prefix to get the clean entity label
- URLs in placemark description become schema_same_as (skip mymaps.usercontent.google.com)
- Multiple Day folders with same N → grouped into one Article node
- Duplicate detection by coordinates within 0.001° tolerance

---

## Step A — Manual UI: Create lodging geo_entity bundle

1. Admin → Structure → Geo entity types → Add geo entity type
   Label: Lodging, machine name: `lodging`

2. Manage fields — add to lodging bundle:
   - `field_body` (text with summary) — description
   - `schema_geo` (Geofield) — coordinates
   - `schema_image` (entity_reference → ImageObject media)
   - `schema_address` (address) — country + admin area only
   - `schema_same_as` (link) — booking URL etc
   - `field_nights` (integer) — number of nights stayed

3. Admin → Structure → Geo entity types → Destination → Manage fields
   Add existing field: `schema_same_as` (reuse storage)

4. Export config and commit:
   ```bash
   lando drush cex
   git add config/sync
   git commit -m "Add lodging geo_entity bundle and schema_same_as to destination"
   ```

---

## Step B — Claude Code: Add LodgingJsonLd to seanmontague_schemadotorg

```
Read the root CLAUDE.md.
Read public_html/modules/custom/seanmontague_schemadotorg/seanmontague_schemadotorg.module.
Read public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/PointOfInterestJsonLd.php.
Read public_html/modules/custom/gvp_schemadotorg/src/JsonLd/VolcanoJsonLd.php (pattern reference).

Create src/JsonLd/LodgingJsonLd.php following the same static class pattern.

Schema.org type: LodgingBusiness (subtype of LocalBusiness → Place).

Methods:
  alter()              — entry point, orchestrates builders
  buildId()            — @id = entity canonical URL + #lodging
  buildGeo()           — geo: GeoCoordinates from schema_geo Geofield
                         (float) cast on lat/lon — Blueprints truncates
  buildAddress()       — address: PostalAddress from schema_address
  buildAdditionalProperties() — nights: PropertyValue from field_nights

Output:
{
  "@type": "LodgingBusiness",
  "@id": "https://seanmontague.com/lodging/premier-inn-dublin#lodging",
  "name": "Premier Inn Dublin Airport hotel",
  "geo": { "@type": "GeoCoordinates", "latitude": 53.4451, "longitude": -6.2260 },
  "address": { "@type": "PostalAddress", "addressCountry": "IE" },
  "additionalProperty": [
    { "@type": "PropertyValue", "name": "nights", "value": 1 }
  ]
}

Also add lodging dispatch to seanmontague_schemadotorg.module:
  if ($entity_type_id === 'geo_entity' && $bundle === 'lodging') {
    LodgingJsonLd::alter($data, $entity, $bubbleable_metadata);
  }

Show all files before writing.
After: lando drush cr
```

---

## Step C — Claude Code: Rewrite ireland_import as generic trip_import

Rename the module from `ireland_import` to `trip_import`. This is a
generic importer for any Google My Maps KMZ following the convention above.

```
Read the root CLAUDE.md.
Read public_html/modules/custom/ireland_import/ (full existing module).
Read the KML naming convention at the top of this file.

Rewrite the module as trip_import with these changes:

MODULE RENAME:
  ireland_import → trip_import
  IrelandImportBatch → TripImportBatch
  IrelandImportForm → TripImportForm
  /admin/config/ireland-import → /admin/config/trip-import

FORM (TripImportForm.php):
  Add fields:
    - trip_title (textfield, required) — used as TouristTrip node title
    - trip_start_date (date, required) — Day 1 date, used to calculate
      per-article schema_date_published (start + N-1 days)
    - kmz_file (managed_file, required) — KMZ upload
    - import_types (checkboxes):
        pois         → Points of Interest → geo_entity:poi
        destinations → Destinations → geo_entity:destination
        lodging      → Lodging → geo_entity:lodging
        routes       → Extract routes + create Article nodes
        trip         → Create TouristTrip node

BATCH (TripImportBatch.php):

parseKmz(string $real_path): ?array
  Parse KML from KMZ zip. Return:
  [
    'pois'         => [['label', 'lon', 'lat', 'url'], ...],
    'destinations' => [['label', 'lon', 'lat', 'url'], ...],
    'lodging'      => [['label', 'lon', 'lat', 'url', 'nights'], ...],
    'days'         => [
      1 => [
        'routes' => [
          ['name', 'slug', 'coords', 'route_type'],
          ...
        ],
        'pois' => ['Trinity College', 'Dublin Castle', ...],
      ],
      ...
    ],
  ]

PARSING RULES:

Folder detection:
  'Points of Interest' → pois
  'Destinations'       → destinations
  'Lodging'            → lodging
  /^Day \d+:/i         → day route folder

Label cleaning (apply to all Point placemarks):
  Strip leading 'Day N: ', 'Day N; ', 'Night N: ', 'Night N, ...: '
  using regex: /^(Day|Night)\s+[\d,\s]+[;:]\s*/i
  Trim result.

URL extraction from description:
  Skip if empty, if starts with https://mymaps.usercontent.google.com
  Otherwise use as schema_same_as value.

Lodging nights:
  Parse 'Night N, Night M: ...' to count nights = M - N + 1
  'Night 5: ...' = 1 night
  'Night 5, Night 6: ...' = 2 nights

Route type detection (per LineString name, case-insensitive):
  walking:  name contains 'walk'
  cycling:  name contains 'inis', 'mór', 'mor', 'aran', 'cycling', 'bicycle', 'bike'
  hiking:   name contains 'reeks', 'aonghasa', 'mountain', 'hike', 'hiking', 'meenabool'
  driving:  everything else (including 'bus', 'decker', city-to-city)

Day grouping:
  Extract day number from folder name: /^Day (\d+):/i
  Group all LineStrings from folders with same day number together.
  Group POI day numbers from 'Day N: POI Name' patterns too.

DUPLICATE DETECTION:
  For all geo entity types — check by coordinates within 0.001° tolerance:
  $ids = entityQuery('geo_entity')
    ->condition('bundle', $bundle)
    ->condition('schema_geo.lat', [$lat - 0.001, $lat + 0.001], 'BETWEEN')
    ->condition('schema_geo.lon', [$lon - 0.001, $lon + 0.001], 'BETWEEN')
    ->execute();
  Skip creation if any match found. Log as SKIPPED in results.

BATCH OPERATIONS:

importPoi(array $item, array $context):
  Clean label (strip day prefix).
  Dedup by coordinates.
  Create geo_entity:poi with schema_geo WKT point.
  Set schema_same_as if URL present.
  Set field_show_on_map = TRUE.

importDestination(array $item, array $context):
  Clean label.
  Dedup by coordinates.
  Create geo_entity:destination.
  Set schema_same_as if URL present.

importLodging(array $item, array $context):
  Clean label.
  Dedup by coordinates.
  Create geo_entity:lodging.
  Set field_nights from parsed nights count.
  Set schema_same_as if URL present.

createDayArticle(int $day, array $routes, string $trip_start_date, array $context):
  Create one Article node per day grouping.
  Title: strip 'Day N: ' from primary route name (longest/first driving route).
  schema_date_published: $trip_start_date + ($day - 1) days.
  field_route_type: primary route type (driving > hiking > cycling > walking priority).
  For each route in $routes:
    - Build GeoJSON FeatureCollection LineString
    - Save to public://geoshape/{slug}.geojson
    - Create data_download media entity pointing to file
  schema_geoshape: attach first route's media entity (primary route).
  Store created nid in $context['results']['day_articles'][$day].

createTrip(string $title, string $start_date, array $context):
  Create node:tourist_trip.
  Title: $title from form.
  schema_trip_dates: item 0 = $start_date (arrivalTime).
  schema_itinerary: ordered Article nids from $context['results']['day_articles'].
  schema_destination: destination geo_entity ids created during import.

RESULTS TABLE:
  Show type, label/file, status (CREATED/SKIPPED/WRITTEN) for every item.
  Summary line: X POIs, X destinations, X lodging, X routes, X articles, 1 trip.

Show all files before writing.
After:
  lando drush pm:uninstall ireland_import (if installed)
  lando drush en trip_import
  lando drush cr
  Access at: /admin/config/trip-import
```

---

## Step D — Manual: Add KMZ files to project

Copy the three KMZ files into the project so they're accessible
for testing without re-uploading:

```bash
mkdir -p public_html/project/kmz
cp Ireland_Trip_Day_1-5_Final.kmz public_html/project/kmz/
cp Lodging.kmz public_html/project/kmz/
cp Destinations.kmz public_html/project/kmz/
```

Add to .gitignore if you don't want KMZ files in the repo, or commit
them as reference data.

---

## Step E — Manual: Run the importer

1. Access /admin/config/trip-import
2. Upload Ireland_Trip_Day_1-5_Final.kmz
3. Fill in:
   - Trip title: Ireland April 2024
   - Trip start date: 2024-04-19
   - Check all import types
4. Submit — review results table
5. Repeat with Lodging.kmz (lodging only checked)
6. Repeat with Destinations.kmz (destinations only checked)

Note: Destinations.kmz has no folder structure — all placemarks
are at the document root. The importer should handle top-level
placemarks as destinations when "Destinations" import type is checked
and no folder wrapper is present.

---

## Route Type Reference (from KMZ analysis)

| Route name | Detected type |
|---|---|
| Day 1: Double Decker Bus to Dublin | driving |
| Day 1: Walking Dublin | walking |
| Day 2: Dublin to Sligo | driving |
| Day 3: Sligo to Galway | driving |
| Day 4: Galway to Doolin to Limerick | driving |
| Day 4: Inis Mór Aran Islands | cycling |
| Day 4: Meenabool to Dún Aonghasa | hiking |
| Day 5: Limerick to Killarny | driving |
| Day 5: McGillicuddy Reeks | hiking |

---

## Expected Import Results (Day 1-5 KMZ)

| Type | Count | Notes |
|---|---|---|
| POIs created | 21 | Day prefix stripped from labels |
| Destinations | 0 | Separate KMZ |
| Lodging | 0 | Separate KMZ |
| Route GeoJSON files | 9 | One per LineString |
| Article nodes | 5 | One per day grouping |
| TouristTrip node | 1 | Ireland April 2024 |

