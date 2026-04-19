# Claude Code Next Steps

Reference: read the **root CLAUDE.md** for Drupal/config/module work.
Read **public_html/themes/custom/surface/CLAUDE.md** for theme/Twig/JS work.
Read **public_html/themes/custom/surface/STORYBOOK.md** before any source/ changes.
Follow the GVP pattern in `public_html/modules/custom/gvp_schemadotorg/` for Schema.org hooks.

---

## Status (April 2026)

| Step | Task | Status |
|---|---|---|
| 0e | Smart Date on tourist_trip + Schema.org hook | ✅ done |
| 0f-i | field_map_tiles on article | ❌ pending — manual UI |
| 0f-ii | Tile sets in trail_mapper + map.js wire | ❌ pending — Claude Code |
| 0g-i | DataDownload media type | ❌ pending — manual UI |
| 0g-ii | GeoShapeConverter + template update | ❌ pending — Claude Code |
| 0g-iii | GPX waypoint → POI extraction | ❌ pending — optional |
| 0g-iv | spatialCoverage Schema.org track | ❌ pending — optional |
| 1a | geo_entity destination bundle | ❌ pending — manual UI |
| 1b | seanmontague_schemadotorg module | ✅ done |
| 2a | route_type taxonomy + field on article | ❌ pending — manual UI |
| 2b | field_key on category + activity_type | ❌ pending — manual UI |
| 3a | leaflet_full_page refactor | ✅ done |
| 3b | seanmontague_map extension module | ✅ done |
| 4a | Schema.org fixes — manual UI (date published, lat/lon precision, copyright) | ❌ pending |
| 4b | TouristTripJsonLd dates fix + view display cleanup | ❌ pending — Claude Code |
| 5 | KMZ importer (Ireland trip) | ❌ pending — Claude Code |

---

## What exists — confirmed from config + module audit

### Modules (custom)
- `trail_mapper` — GeoJSON, tile config, elevation unit ✅
- `leaflet_full_page` — generic, NMNH stripped, configurable endpoint ✅
- `seanmontague_schemadotorg` — TouristTripJsonLd, PointOfInterestJsonLd, ArticleJsonLd ✅
- `seanmontague_map` — GeoJSON endpoint `/sean-map/items`, popup templates ✅

### Content model
- Article: `body`, `field_image`, `schema_*` fields — view displays clean ✅
- TouristTrip: `field_trip_dates` (Smart Date cardinality 1) + `schema_trip_dates` (cardinality 2, legacy — remove) ✅/⚠️
- geo_entity `poi` bundle: `field_body`, `schema_geo`, `schema_image`, `schema_address`, `schema_place`, `field_show_on_map` ✅
- geo_entity `destination` bundle: NOT YET CREATED ❌

### Issues found
- `schema_trip_dates` (cardinality 2) still exists alongside `field_trip_dates` (cardinality 1) — legacy, should be removed
- tourist_trip view display default shows too many fields — needs cleanup (Step 4)

---

## Step 0f — Per-article tile set override (PENDING)

### 0f-i — Manual UI
1. Admin → Structure → Content types → Article → Manage fields
   Add field: List (text), machine name `field_map_tiles`, label "Map Tiles"
   Allowed values:
     osm|OpenStreetMap
     open-topo|OpenTopoMap (global topo — good for hiking/travel)
     esri-topo|ESRI World Topo
     usgs-topo|USGS National Map (US only, default)
   Not required.

2. Add to Location & Trip group in form display.

3. Export: `lando drush cex && git add config/sync && git commit -m "Add field_map_tiles to article"`

### 0f-ii — Claude Code

```
Read the root CLAUDE.md.
Read public_html/modules/custom/trail_mapper/src/Form/TrailMapperSettingsForm.php.
Read public_html/themes/custom/surface/templates/content/node--article.html.twig.
Read public_html/themes/custom/surface/source/patterns/components/map/map.js.

1. TRAIL_MAPPER — add open-topo and esri-topo to tile definitions.
   Add allTileSets() static method returning full keyed array.
   Pass to drupalSettings as tileSets in hook_page_attachments.

2. NODE TEMPLATE — read field_map_tiles.value, pass as tiles param
   to article collection include.

3. MAP.JS — verify data-tiles → tileSets lookup resolution order.
   Show current tile code before any changes.

Show all changed files before writing. After: lando drush cr
```

---

## Step 0g — GPX: DataDownload + Schema.org (PENDING)

### 0g-i — Manual UI
1. Admin → Structure → Media types → Add
   Name: Data Download, machine name: `data_download`
   Source: File, allowed: gpx, geojson, json
2. Map to Schema.org DataDownload via Blueprints
3. Change `schema_geoshape` on article to entity reference → data_download media
   (may need delete/recreate — check if field type can change in place)
4. Export config and commit.

### 0g-ii — Claude Code
```
Read the root CLAUDE.md.
Read public_html/modules/custom/trail_mapper/src/Service/GeoShapeConverter.php.
Read public_html/themes/custom/surface/templates/content/node--article.html.twig.

Update GeoShapeConverter to traverse media entity for file URI.
Update template to traverse media entity for GeoJSON URL.
Add "Download GPX" link when source file is .gpx.
Show both files before writing. After: lando drush cr
```

### 0g-iii — Waypoint → POI extraction (optional, after 1a)
### 0g-iv — spatialCoverage Schema.org (optional, after seanmontague_schemadotorg ArticleJsonLd)

---

## Step 1a — geo_entity destination bundle (PENDING — manual UI)

1. Admin → Structure → Geo entity types → Add
   Label: Destination, machine name: `destination`

2. Fields:
   - `field_body` (text with summary) — popup description
   - `schema_geo` (Geofield) — coordinates
   - `schema_image` (entity_reference → ImageObject) — popup image
   - `schema_address` (address) — country + admin area only

3. Update `schema_destination` on tourist_trip:
   Change target bundle from place node → geo_entity:destination
   Update Inline Entity Form to show: title, schema_geo, schema_address (country only)

4. Note: Ireland trip Place node destinations need recreating as
   geo_entity:destination records after this change.

5. Export config and commit.

---

## Step 2a — route_type taxonomy + field on article (PENDING — manual UI)

1. Admin → Structure → Taxonomy → Add vocabulary
   Name: Route Type, machine name: `route_type`
   Add terms:
     Driving (key: driving)
     Walking (key: walking)
     Hiking (key: hiking)
     Cycling (key: cycling)

2. Admin → Structure → Content types → Article → Manage fields
   Add field: Entity reference → route_type, machine name `field_route_type`,
   label "Route Type", cardinality 1, not required.

3. Add to Location & Trip group in form display.

4. Map line styling by route_type (for The Map and article maps):
   driving → --muted dashed
   walking → --forest solid
   hiking  → --trail solid
   cycling → --sky solid

5. Export config and commit.

---

## Step 2b — field_key on taxonomy terms (PENDING — manual UI)

1. Admin → Structure → Taxonomy → Category → Manage fields
   Add: Text (plain), machine name `field_key`, label "Key", max 32 chars

2. Admin → Structure → Taxonomy → Activity Type → Manage fields
   Add existing: field_key (reuse storage)

3. Populate category terms:
   Kingdom Trails → trails | Burke Mountain → ski | Permaculture → permaculture
   Drupal → drupal | Maps → maps | Travel → travel

4. Populate activity_type terms:
   Mountain Biking → bike | Hiking → hike | Skiing → ski | Telemark → ski

5. Export config and commit.

---

## Step 4 — Schema.org fixes + tourist_trip cleanup (PENDING — Claude Code + manual)

### 4a — Manual UI

1. TOURIST_TRIP: Add `schema_date_published` date field
   Admin → Structure → Content types → Tourist Trip → Manage fields
   Add existing field: schema_date_published (reuse storage from article)
   Label: "Date Published", not required
   Add to Content group in form display after field_trip_dates.

2. PLACE: Increase lat/lon decimal precision
   Admin → Structure → Content types → Place → Manage fields
   Edit schema_latitude and schema_longitude — increase precision to 6 decimal places.

3. SITE-WIDE: Fix copyrightYear
   Admin → Config → Search and metadata → Schema.org: JSON-LD
   Find the copyrightYear setting — change from dynamic current year
   to use node creation year or remove it.

4. Export config and commit.

### 4b — Claude Code: TouristTripJsonLd + view display cleanup

```
Read the root CLAUDE.md.
Read public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/TouristTripJsonLd.php.
Read public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/PointOfInterestJsonLd.php.
Read config/sync/core.entity_view_display.node.tourist_trip.default.yml.

Fix 1 — TouristTripJsonLd::buildDates():
  schema_trip_dates has cardinality 2.
  Item 0 = departure, item 1 = arrival.
  Cannot be mapped via Blueprints UI (one field → two properties).
  Fix to read both items:

  $items = $entity->get('schema_trip_dates');
  $item0 = $items->get(0);
  $item1 = $items->get(1);
  if ($item0) {
    $start = $item0->get('value')->getValue();
    if ($start) $data['departureTime'] = date('Y-m-d', $start);
  }
  if ($item1) {
    $end = $item1->get('value')->getValue();
    if ($end) $data['arrivalTime'] = date('Y-m-d', $end);
  }
  Also remove field_trip_dates from buildDates() — schema_trip_dates
  is the correct field.

Fix 2 — PointOfInterestJsonLd::buildGeo():
  Blueprints truncates lat/lon decimal fields to low precision.
  Ensure geo coordinates are cast to float with full precision:
  'latitude'  => (float) $lat,
  'longitude' => (float) $lon,

Fix 3 — View display default cleanup:
  tourist_trip.default currently shows too many fields.
  node--trip.html.twig handles all rendering via direct field access.
  Set visible: field_body only (label hidden).
  Set hidden: everything else.
  Also remove schema_trip_dates from visible if it's rendering
  via Leaflet formatter (same issue as schema_geo on article).

Fix 4 — Remove legacy field_trip_dates:
  field_trip_dates (cardinality 1) is superseded by schema_trip_dates
  (cardinality 2). If field_trip_dates has no content, delete it:
  Admin → Structure → Content types → Tourist Trip → Manage fields
  Delete field_trip_dates.
  Note: check for content first — lando drush php-eval
  "echo \Drupal::entityQuery('node')->condition('type','tourist_trip')
  ->condition('field_trip_dates',NULL,'IS NOT NULL')->count()->execute();"

Show all changed files before writing.
After: lando drush cim && lando drush cr
```

## Step 5 — KMZ importer for Ireland trip (PENDING — Claude Code)

Two KMZ files:
- `public_html/project/Ireland_Trip_Day_1-5_Final.kmz`
- `public_html/project/Ireland_Trip_Day_6___7_Final.kmz`

```
Read the root CLAUDE.md.
Read public_html/modules/custom/trail_mapper/trail_mapper.module.

Create a Drush script or migration to import Ireland trip data from
the two KMZ files. KMZ is a zipped KML — unzip to access doc.kml.

The script should:

1. SITES → geo_entity:poi records
   Parse all <Placemark> elements in the Sites folder.
   For each: create a geo_entity with bundle poi,
   label = placemark name, schema_geo = coordinates.
   Skip if a poi with the same label already exists.

2. LODGING → geo_entity:destination records (after Step 1a)
   Parse "Lodging Full Trip" folder placemarks.
   Create geo_entity:destination records.

3. DAILY ROUTES → GeoJSON files
   Parse each named Day folder (Day 1 Walking Dublin, Day 2, Day 3 etc).
   For each route <Placemark> with <LineString>:
   - Extract coordinates
   - Convert to GeoJSON LineString FeatureCollection
   - Save as public://geoshape/{day-slug}.geojson
   - Output a summary of files created

4. ROUTE TYPE MAPPING
   Detect route type from folder name:
     "Walking" or "Inis Mór" → walking
     "McGillicuddy Reeks" → hiking
     Driving day routes → driving

Output a summary table of what was created/skipped.
Show full implementation before running.
Run with: lando drush php:script scripts/ireland-import.php
```

---

## URL Aliases

Set manually on each node:

| Content | Pattern |
|---|---|
| Trail report | `/trails/bike/[title]`, `/trails/hike/[title]`, `/trails/ski/[title]` |
| Trip | `/trips/[title]` |
| Trip article | `/trips/[trip-title]/[article-title]` |
| Drupal post | `/drupal/[title]` |
| Permaculture | `/permaculture/[title]` |
| Writing | `/writing/[title]` |
| Point of Interest | `/places/[title]` |

---

## Execution order

Manual first: 0f-i → 1a → 2a → 2b
Then Claude Code: 0f-ii → 0g-i → 0g-ii → 4 → 5
Optional: 0g-iii → 0g-iv
