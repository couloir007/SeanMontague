# Claude Code Next Steps

Reference: read the **root CLAUDE.md** for Drupal/config/module work.
Read **public_html/themes/custom/surface/CLAUDE.md** for theme/Twig/JS work.
Read **public_html/themes/custom/surface/STORYBOOK.md** before any source/ changes.
Follow the GVP pattern in `public_html/modules/custom/gvp_schemadotorg/` for Schema.org hooks.

All custom modules should use `package: Sean Montague` (not `package: Custom`).

---

## Status (April 2026)

| Step | Task | Status |
|---|---|---|
| 0e | Smart Date + Schema.org dates hook | ✅ done |
| 0f-i | field_map_tiles on article | ✅ done |
| 0f-ii | Tile sets in trail_mapper + map.js | ❌ pending |
| 0g-i | DataDownload media type | ❌ pending — manual UI |
| 0g-ii | GeoShapeConverter + template | ❌ pending — Claude Code |
| 1a | geo_entity destination bundle | ✅ done |
| 1b | seanmontague_schemadotorg module | ✅ done |
| 2a | route_type taxonomy + field_route_type | ❌ pending — manual UI |
| 2b | field_key on taxonomies | ❌ pending — manual UI |
| 3a | leaflet_full_page refactor | ✅ done |
| 3b | seanmontague_map module | ✅ done |
| 4a | Add schema_date_published to tourist_trip | ✅ done |
| 4b | Schema.org fixes (Claude Code) | ❌ pending |
| M  | Module package rename + removals | ❌ pending — Claude Code |
| N  | Rename leaflet_full_page → map_page | ❌ pending — Claude Code |
| 5  | KMZ importer with UI | ❌ pending — Claude Code |

---

## Step 0f-ii — Tile sets in trail_mapper + map.js (PENDING)

```
Read the root CLAUDE.md.
Read public_html/modules/custom/trail_mapper/src/Form/TrailMapperSettingsForm.php.
Read public_html/themes/custom/surface/templates/content/node--article.html.twig.
Read public_html/themes/custom/surface/source/patterns/components/map/map.js.

1. TRAIL_MAPPER — add open-topo and esri-topo tile definitions.
   Add allTileSets() static method. Pass all sets to drupalSettings
   as tileSets in hook_page_attachments.

2. NODE TEMPLATE — read field_map_tiles.value, pass as tiles param.

3. MAP.JS — verify data-tiles → tileSets lookup. Show current code first.

Show all changed files before writing. After: lando drush cr
```

---

## Step 0g — GPX as DataDownload (PENDING)

### 0g-i — Manual UI
1. Admin → Structure → Media types → Add
   Name: Data Download, machine name: `data_download`, source: File
   Allowed: gpx, geojson, json
2. Map to Schema.org DataDownload via Blueprints
3. Change `schema_geoshape` on article to entity reference → data_download
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

---

## Step 2a — route_type taxonomy (PENDING — manual UI)

1. Admin → Structure → Taxonomy → Add vocabulary
   Name: Route Type, machine name: `route_type`
   Terms: Driving (driving), Walking (walking), Hiking (hiking), Cycling (cycling)

2. Admin → Structure → Content types → Article → Manage fields
   Add: Entity reference → route_type, machine name `field_route_type`,
   label "Route Type", cardinality 1, not required.
   Add to Location & Trip group in form display.

3. Map line styling by route_type:
   driving → --muted dashed | walking → --forest solid
   hiking  → --trail solid  | cycling → --sky solid

4. Export config and commit.

---

## Step 2b — field_key on taxonomies (PENDING — manual UI)

1. Category → Add field_key (text plain, max 32)
   Terms: Kingdom Trails→trails, Burke Mountain→ski,
   Permaculture→permaculture, Drupal→drupal, Maps→maps, Travel→travel

2. Activity Type → Add existing field_key
   Terms: Mountain Biking→bike, Hiking→hike, Skiing→ski, Telemark→ski

3. Export config and commit.

---

## Step 4b — Schema.org fixes (PENDING — Claude Code)

```
Read the root CLAUDE.md.
Read public_html/modules/custom/seanmontague_schemadotorg/seanmontague_schemadotorg.module.
Read public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/TouristTripJsonLd.php.
Read public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/PointOfInterestJsonLd.php.
Read public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/ArticleJsonLd.php.
Read config/sync/core.entity_view_display.node.tourist_trip.default.yml.

Fix 1 — TouristTripJsonLd::buildDates():
  Swap departureTime/arrivalTime — trip is TO Ireland so:
    start value (item 0) = arrivalTime (arrived in Ireland)
    end value   (item 1) = departureTime (departed Ireland)
  Current code reads .value and .end_value from first() — confirm
  this matches actual Smart Date field structure and fix if needed.

Fix 2 — PointOfInterestJsonLd::buildGeo():
  Blueprints truncates lat/lon regardless of field precision.
  Cast to float to bypass Blueprints formatter:
    'latitude'  => (float) $lat,
    'longitude' => (float) $lon,
  Apply same float cast anywhere Place coordinates appear.

Fix 3 — Copyright year:
  No site-wide Blueprints setting exists for copyrightYear.
  Implement hook_schemadotorg_jsonld_alter() in the module.
  Read schema_date_published from the mainEntity node:
    $data['schemadotorg_jsonld_entity']['copyrightYear'] =
      (int) date('Y', strtotime($published));
  Fall back to node created year if schema_date_published is empty.

Fix 4 — ArticleJsonLd::buildMentions() crash:
  schema_poi field does not exist yet on article bundle.
  Add hasField() guard:
    if (!$entity->hasField('schema_poi') ||
        $entity->get('schema_poi')->isEmpty()) {
      return;
    }

Fix 5 — tourist_trip view display:
  node--trip.html.twig handles all rendering directly.
  Set visible: field_body only. Hide everything else.

Fix 6 — Remove legacy field_trip_dates:
  Superseded by schema_trip_dates. Confirm no content exists, then
  advise deleting via UI (Admin → Tourist Trip → Manage fields).

Show all changed files before writing.
After: lando drush cim && lando drush cr
```

---

## Step M — Module package rename + removals (PENDING — Claude Code)

```
Read the root CLAUDE.md.

1. RENAME package in all custom modules:
   Change 'package: Custom' → 'package: Sean Montague'
   in every .info.yml under public_html/modules/custom/
   Exception: do not change global_volcanism or gvp_* modules
   (they belong to Smithsonian, not seanmontague.com).

2. REMOVE these modules (safe to delete — no active use on this site):
   - custom_entity — scaffolding placeholder, @todo, no content
   - geo_content_builder — superseded by geo_entity contrib module
   - global_volcanism — Smithsonian GVP, not for this site
   - gvp_external_resources — Smithsonian GVP, not for this site

   Before deleting each, verify:
   lando drush php-eval "echo \Drupal::moduleHandler()
     ->moduleExists('MODULE_NAME') ? 'enabled' : 'disabled';"
   Only delete if disabled. If enabled, disable first:
   lando drush pm:uninstall MODULE_NAME

3. Show all .info.yml changes before writing.
   After removals: lando drush cr
```

---

## Step N — Rename leaflet_full_page → map_page (PENDING — Claude Code)

Complete module rename. Must be done while the module is DISABLED on
the live site to avoid config ID conflicts.

Before running: `lando drush pm:uninstall leaflet_full_page`

```
Read the root CLAUDE.md.
Read all files in public_html/modules/custom/leaflet_full_page/.

Rename the module from leaflet_full_page to map_page throughout.
This is a mechanical find-and-replace across all files plus a
directory rename. New module lives at:
  public_html/modules/custom/map_page/

Rename map:
  leaflet_full_page          → map_page
  LeafletFullPage            → MapPage
  leaflet-full-page          → map-page
  Leaflet Full Page          → Map Page
  LeafletMapInteraction.js   → map-interaction.js
  LeafletMapItemsService.php → MapItemsService.php
  LeafletFullPageDisplayExtender.php → MapPageDisplayExtender.php
  leaflet_full_page_display_extender → map_page_display_extender
  drupalSettings.leaflet_full_page   → drupalSettings.map_page

Files requiring changes:
  leaflet_full_page.info.yml       → map_page.info.yml
  leaflet_full_page.module         → map_page.module
  leaflet_full_page.routing.yml    → map_page.routing.yml
  leaflet_full_page.services.yml   → map_page.services.yml
  leaflet_full_page.libraries.yml  → map_page.libraries.yml
  leaflet_full_page.install        → map_page.install
  config/install/leaflet_full_page.settings.yml → map_page.settings.yml
  config/install/views.view.leaflet_full_page.yml → views.view.map_page.yml
  src/Plugin/views/display_extender/LeafletFullPageDisplayExtender.php
  src/Service/LeafletMapItemsService.php → src/Service/MapItemsService.php
  src/Controller/MapItemsController.php (namespace only)
  src/Form/SettingsForm.php (namespace + config key)
  js/LeafletMapInteraction.js → js/map-interaction.js

URL paths update:
  /leaflet-full-page/map-items     → /map-page/map-items
  /leaflet-full-page/map-items/{bundle} → /map-page/map-items/{bundle}
  /admin/config/leaflet-full-page/settings → /admin/config/map-page/settings

Also update package: 'Sean Montague' in map_page.info.yml.

Also update seanmontague_map if it references leaflet_full_page anywhere.

Show the complete list of changes before writing any files.
After writing:
  lando drush en map_page
  lando drush cr
  Verify settings at /admin/config/map-page/settings
  Verify endpoint at /map-page/map-items
```

## Step 5 — KMZ importer with admin UI (PENDING — Claude Code)

```
Read the root CLAUDE.md.

Create a new module public_html/modules/custom/ireland_import/ with:

ireland_import.info.yml:
  name: 'Ireland Trip Importer'
  package: 'Sean Montague'
  description: 'Admin UI to import KMZ files as geo_entity records
    and GeoJSON route files for the Ireland April 2024 trip.'

Admin form at /admin/config/ireland-import with:
  - File upload field for KMZ file (or path to existing KMZ)
  - Checkboxes: Import Sites (POIs), Import Lodging (Destinations),
    Extract Routes (GeoJSON)
  - Submit button → runs import batch

The importer parses KML from KMZ (unzip in memory):

SITES folder → geo_entity:poi records
  label = placemark name
  schema_geo = coordinates (Geofield WKT point)
  Skip if poi with same label already exists.

Lodging folder → geo_entity:destination records
  label = simplified name (strip address cruft)
  schema_geo = coordinates

Day folders (Day 1, Day 2 etc) → GeoJSON LineString files
  saved to public://geoshape/{slug}.geojson
  Route type detection from folder name:
    contains "Walking" or "Inis" → walking
    contains "Reeks" or "Dún" → hiking
    otherwise → driving
  Output a results table showing files created.

KMZ files are at:
  public_html/project/Ireland_Trip_Day_1-5_Final.kmz
  public_html/project/Ireland_Trip_Day_6___7_Final.kmz

Show full implementation before writing.
After: lando drush en ireland_import && lando drush cr
Access at: /admin/config/ireland-import
```

---

## URL Aliases

Set manually. Convention:

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

Manual first: 2a → 2b → 0g-i
Claude Code: M → 4b → 0f-ii → 0g-ii → 5
Optional: 0g-iii → 0g-iv
