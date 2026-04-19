# Claude Code Next Steps

Reference: read the **root CLAUDE.md** for Drupal/config/module work.
Read **public_html/themes/custom/surface/CLAUDE.md** for theme/Twig/JS work.
Read `public_html/modules/custom/gvp_schemadotorg/` for the Schema.org
hook pattern used throughout this project.

---

## Status (April 2026)

| Step | Task | Status |
|---|---|---|
| 0e | Trip form display, view displays, template | ❌ pending |
| 0f | field_map_tiles + tile sets | ❌ pending |
| 0g | GPX / DataDownload / POI / Schema.org track | ❌ pending |
| 1  | geo_entity poi bundle + seanmontague_schemadotorg module | ❌ pending |
| 2  | field_key on taxonomies | ❌ pending |
| 3  | leaflet_full_page refactor (generic + site extension) | ❌ pending |
| 4  | elevation_unit config sync | ❌ pending |

---

## Step 0e — Trip display and template fixes (PENDING)

All fields exist. Three config fixes + one template fix needed.

```
Read the root CLAUDE.md.
Read public_html/themes/custom/surface/CLAUDE.md.
Read config/sync/core.entity_form_display.node.tourist_trip.default.yml.
Read config/sync/core.entity_view_display.node.tourist_trip.default.yml.
Read config/sync/core.entity_view_display.node.tourist_trip.teaser.yml.
Read public_html/themes/custom/surface/templates/content/node--trip.html.twig.

Fix 1 — FORM DISPLAY: date order wrong, departure must come before arrival.
Update group_content children:
  title, field_departure_date, field_arrival_date, schema_image, field_body

Fix 2 — VIEW DISPLAY TEASER: only links and schema_image visible.
Add:
  field_departure_date: date_default, label hidden, format_type html_date, weight 1
  field_arrival_date:   date_default, label hidden, format_type html_date, weight 2
  field_body: text_summary_or_trimmed, label hidden, trim_length 150, weight 3
  links: weight 4
  schema_image stays at weight 0
Hide everything else.

Fix 3 — VIEW DISPLAY DEFAULT: field_body is correctly visible. No change needed.

Fix 4 — TEMPLATE bugs:
  a) Uses content.body|field_value — field is field_body not body.
     Fix: content.field_body|field_value
  b) Date still uses schema_date_published — needs field_departure_date
     and field_arrival_date.

Replace the date logic with:
  {% set depart = node.field_departure_date.value %}
  {% set arrive = node.field_arrival_date.value %}
  {% if depart and arrive %}
    {% set date_display = depart|date('F j') ~ '–' ~ arrive|date('F j, Y') %}
  {% elseif depart %}
    {% set date_display = depart|date('F j, Y') %}
  {% else %}
    {% set date_display = node.schema_date_published.value|date('F j, Y') %}
  {% endif %}

Pass date_display to the article-header include.

Show all changed files before writing.
After: lando drush cim && lando drush cr
```

---

## Step 0f — Per-article tile set override (PENDING)

### 0f-i — Manual: add field via UI

1. Admin → Structure → Content types → Article → Manage fields
   Add field: List (text), machine name field_map_tiles, label "Map Tiles"
   Allowed values:
     osm|OpenStreetMap
     open-topo|OpenTopoMap (global topo — good for hiking/travel)
     esri-topo|ESRI World Topo
     usgs-topo|USGS National Map (US only, default)
   Not required.

2. Admin → Structure → Content types → Article → Manage form display
   Add field_map_tiles to the Location & Trip group after schema_geo.

3. Export config:
   lando drush cex
   git add config/sync
   git commit -m "Add field_map_tiles tile override to article"

### 0f-ii — Claude Code: register tile sets + wire override

```
Read the root CLAUDE.md.
Read public_html/themes/custom/surface/CLAUDE.md.
Read public_html/modules/custom/trail_mapper/src/Form/TrailMapperSettingsForm.php.
Read public_html/themes/custom/surface/templates/content/node--article.html.twig.
Read public_html/themes/custom/surface/source/patterns/components/map/map.js.

Three changes:

1. TRAIL_MAPPER — add missing tile sets and pass all as tileSets to
   drupalSettings so map.js can look them up by key:

   'tileSets' => TrailMapperSettingsForm::allTileSets(),

   allTileSets() returns:
   [
     'usgs-topo' => ['url' => 'https://{s}.tile.opentopomap.org...',  'attribution' => '...', 'maxZoom' => 16],
     'osm'       => ['url' => 'https://{s}.tile.openstreetmap.org...','attribution' => '...', 'maxZoom' => 19],
     'open-topo' => ['url' => 'https://{s}.tile.opentopomap.org...',  'attribution' => '...', 'maxZoom' => 17],
     'esri-topo' => ['url' => 'https://server.arcgisonline.com/...',   'attribution' => '...', 'maxZoom' => 18],
   ]

2. NODE TEMPLATE — read field_map_tiles and pass to collection:
   {% set tiles = node.field_map_tiles.value %}
   Add 'tiles': tiles to the article collection include parameters.

3. MAP.JS — verify tile resolution order:
   data-tiles attribute → drupalSettings.trailMapper.tileKey → usgs-topo
   When data-tiles is set, look up full config from
   drupalSettings.trailMapper.tileSets[key].
   Show the current tile resolution code before making any changes.

Show all changed files before writing.
After: lando drush cr
```

---

## Step 0g — GPX: DataDownload + waypoint POIs + Schema.org track (PENDING)

Three sub-steps. 0g-i and 0g-ii are the core work. 0g-iii and 0g-iv
depend on Step 1 (geo_entity POI bundle + seanmontague_schemadotorg).

### 0g-i — Manual: create DataDownload media type

1. Admin → Structure → Media types → Add media type
   Name: Data Download, machine name: data_download
   Media source: File
   Allowed extensions: gpx, geojson, json

2. Map to Schema.org via Blueprints:
   Schema.org type: DataDownload
   contentUrl → file field

3. Admin → Structure → Content types → Article → Manage fields
   Change schema_geoshape from File → Entity reference → media
   Target bundle: data_download
   Note: may require deleting and recreating the field if type
   cannot be changed in place.

4. Export config:
   lando drush cex
   git add config/sync
   git commit -m "Add DataDownload media type for GPX files"

### 0g-ii — Claude Code: update GeoShapeConverter + template

```
Read the root CLAUDE.md.
Read public_html/modules/custom/trail_mapper/src/Service/GeoShapeConverter.php.
Read public_html/themes/custom/surface/templates/content/node--article.html.twig.

Two changes:

1. GEOSHAPECONVERTER — update file URI access to traverse media entity.
   The field is now an entity reference to data_download media, not
   a direct file reference.
   was: $entity->schema_geoshape->entity->getFileUri()
   now: $entity->schema_geoshape->entity->{source_field}->entity->getFileUri()
   Inspect the data_download media bundle to find the correct source
   field name before writing.

2. NODE TEMPLATE — update GeoJSON URL:
   was: node.schema_geoshape.entity.uri.value|file_url
   now: traverse the media entity to its source file URI.
   Also add a "Download GPX" link when the source file extension is .gpx:
     {% set geoshape_media = node.schema_geoshape.entity %}
     {% if geoshape_media %}
       {% set geo_file = geoshape_media.{source_field}.entity %}
       {% set geojson_url = geo_file.fileuri|file_url %}
       {% set is_gpx = geo_file.filename matches '/\\.gpx$/i' %}
     {% endif %}

Show both files before making changes.
After: lando drush cr
```

### 0g-iii — Claude Code: GPX waypoint → POI extraction (OPTIONAL)
Requires Step 1 (geo_entity poi bundle) to be complete first.

```
Read the root CLAUDE.md.
Read public_html/modules/custom/trail_mapper/src/Service/GeoShapeConverter.php.

GPX files may contain <wpt> elements (waypoints) — named points along
the route such as trailheads, summits, huts, or notable features.
Currently GeoShapeConverter discards waypoints and only processes the
track (<trkpt>).

Add an optional waypoint extraction step to GeoShapeConverter::processEntity():

1. After converting the track, check if the source file was a .gpx.
2. Parse <wpt> elements from the GPX XML.
3. For each waypoint with a name and coordinates, check if a geo_entity
   with bundle 'poi' already exists with matching coordinates (within
   0.001 degree tolerance). If not, create a new geo_entity:
     - label: waypoint name
     - bundle: poi
     - field_geo (Geofield): lat/lon from waypoint
     - field_address: country code from the article's schema_place if available
4. Gate this behavior behind a trail_mapper setting:
   'extract_waypoints' => FALSE by default.
   Add the setting to TrailMapperSettingsForm and trail_mapper.settings schema.

Show full implementation before writing.
After: lando drush cr
```

### 0g-iv — Schema.org track output (OPTIONAL)
Requires Step 1 (seanmontague_schemadotorg module) to be complete first.

```
Read public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/ArticleJsonLd.php.

Add spatialCoverage to BlogPosting JSON-LD when schema_geoshape is set.
Extract a simplified WKT LineString from the GeoJSON (first Feature,
first coordinate pair per segment, max 50 points to keep JSON-LD compact).

Output:
"spatialCoverage": {
  "@type": "GeoShape",
  "line": "44.59,-71.94 44.60,-71.93 44.61,-71.92 ..."
}

Only emit if schema_geoshape media entity is present and GeoJSON is valid.
Show implementation before writing.
After: lando drush cr
```

---

## Step 1 — geo_entity POI bundle + seanmontague_schemadotorg module (PENDING)

This step introduces Points of Interest and the Schema.org hook module.
Two sub-steps.

### 1a — Manual: create geo_entity poi bundle

The geo_entity contrib module is already installed. Add a poi bundle:

1. Admin → Structure → Geo entity types → Add geo entity type
   Label: Point of Interest, machine name: poi

2. Manage fields — add to poi bundle:
   - body (text_with_summary) — teaser description for map popup
   - field_geo (Geofield) — reuse existing geo_entity geofield storage
   - schema_image (entity_reference → ImageObject media) — popup card image
   - schema_address (address) — country minimum
   - schema_place (entity_reference → place node) — parent Place
   - field_show_on_map (boolean) — include in The Map, default TRUE

3. Admin → Structure → Content types → Article → Manage fields
   Add field: Entity reference, machine name schema_poi,
   label "Points of Interest", target: geo_entity, bundle: poi
   Cardinality: unlimited. Widget: Inline Entity Form - Complex.

4. Export config:
   lando drush cex
   git add config/sync
   git commit -m "Add geo_entity poi bundle and schema_poi field to article"

### 1b — Claude Code: create seanmontague_schemadotorg module

Follow the pattern from public_html/modules/custom/gvp_schemadotorg/
exactly. That module is the canonical reference for this project.

```
Read the root CLAUDE.md.
Read public_html/modules/custom/gvp_schemadotorg/gvp_schemadotorg.module.
Read public_html/modules/custom/gvp_schemadotorg/src/JsonLd/VolcanoJsonLd.php.

Create public_html/modules/custom/seanmontague_schemadotorg/ with:

seanmontague_schemadotorg.info.yml:
  name: 'SeanMontague Schema.org'
  type: module
  description: 'Custom Schema.org JSON-LD for seanmontague.com. Extends
    SchemaBlueprints with TouristAttraction (geo_entity poi) and
    BlogPosting enhancements (POI mentions, spatial coverage).'
  package: Custom
  core_version_requirement: ^10 || ^11
  dependencies:
    - drupal:node
    - schemadotorg:schemadotorg
    - schemadotorg:schemadotorg_jsonld
    - geo_entity:geo_entity

seanmontague_schemadotorg.module:
  Implements hook_schemadotorg_jsonld_schema_type_entity_alter().
  Dispatches by entity type + bundle:
    geo_entity, bundle poi → PointOfInterestJsonLd::alter()
    node, bundle article  → ArticleJsonLd::alter()

src/JsonLd/PointOfInterestJsonLd.php:
  Builds TouristAttraction JSON-LD for geo_entity poi bundle.
  Methods:
    buildId()              — @id = entity URL + #poi fragment
    buildGeo()             — geo: GeoCoordinates from field_geo Geofield
                             $item->get('lat')->getValue()
                             $item->get('lon')->getValue()
    buildContainedInPlace()— schema_place referenced node → Place name
    buildImage()           — schema_image media → ImageObject url

  Output:
  {
    "@type": "TouristAttraction",
    "@id": "https://seanmontague.com/poi/dun-aonghasa#poi",
    "name": "Dún Aonghasa",
    "description": "Iron Age cliff fort...",
    "image": { "@type": "ImageObject", "url": "..." },
    "geo": { "@type": "GeoCoordinates", "latitude": 53.12, "longitude": -9.77 },
    "containedInPlace": { "@type": "Place", "name": "Inishmore" }
  }

src/JsonLd/ArticleJsonLd.php:
  Builds BlogPosting additions for article nodes.
  Methods:
    buildMentions() — loops schema_poi entity refs, outputs each as:
      { "@type": "TouristAttraction",
        "@id": "...#poi",
        "name": "..." }
    buildSpatialCoverage() — placeholder, implemented in Step 0g-iv.

Show all files before writing.
After: lando drush en seanmontague_schemadotorg && lando drush cr
```

---

## Step 2 — field_key on taxonomy terms (PENDING)

### 2a — Manual: create field via UI

1. Admin → Structure → Taxonomy → Category → Manage fields
   Add field: Text (plain), machine name field_key, label "Key", max 32 chars

2. Admin → Structure → Taxonomy → Activity Type → Manage fields
   Add existing field: field_key (reuse same storage)

3. Populate category terms:
   Kingdom Trails → trails | Burke Mountain → ski | Permaculture → permaculture
   Drupal → drupal | Maps → maps | Travel → travel

4. Populate activity_type terms:
   Mountain Biking → bike | Hiking → hike | Skiing → ski | Telemark → ski
   Permaculture → permaculture

### 2b — Export config

```bash
lando drush cex
git add config/sync
git commit -m "Add field_key to category and activity_type taxonomies"
```

---

## Step 3 — leaflet_full_page refactor (PENDING)

Refactor into a generic base module and a site-specific extension.
Detailed plan in CLAUDE.md. Two sub-steps.

### 3a — Claude Code: strip NMNH specifics from leaflet_full_page

```
Read the root CLAUDE.md.
Read public_html/modules/custom/leaflet_full_page/leaflet_full_page.module.
Read public_html/modules/custom/leaflet_full_page/leaflet_full_page.routing.yml.
Read public_html/modules/custom/leaflet_full_page/src/Controller/MapItemsController.php.
Read public_html/modules/custom/leaflet_full_page/js/LeafletMapInteraction.js.

Remove all NMNH/Smithsonian-specific code:
  - Remove EdanProxyController, EdanTestController, LeafletEdanObjectService
    and all EDAN connector dependencies
  - Remove preprocess_block__gesso_smithsonianbrandingmap (hardcoded NMNH block)
  - Remove preprocess_views_view__leaflet_full_page__legend hardcoded route/node
  - Remove US state FIPS logic from LeafletMapInteraction.js
  - Remove StateCoordinatesMap.js
  - Remove usa.geojson and state boundary includes
  - Remove NMNH images
  - Remove semiquincentennial bundle references from MapItemsController

Make MapItemsController generic:
  - Accept bundle name as a config value or route parameter
  - Accept field map (which fields → which JSON keys) from config
  - Output standard GeoJSON FeatureCollection

Make the JSON endpoint URL configurable via module settings.

Keep:
  - geo_entity dependency
  - Views display extender plugin
  - LeafletMapItemsService base (strip EDAN methods)
  - Core JS map initialization (strip state navigation)
  - Popup template (overridable)

Show all changed files before writing.
After: lando drush cr
```

### 3b — Claude Code: create seanmontague_map extension module

```
Read the root CLAUDE.md.
Read public_html/modules/custom/leaflet_full_page/ (refactored version).

Create public_html/modules/custom/seanmontague_map/ as the site-specific
extension of leaflet_full_page for The Map.

The Map aggregates geo data from four sources:
  - Place nodes (schema_destination on trips) — sky markers
  - geo_entity poi bundle (field_show_on_map = TRUE) — forest markers
  - Article nodes with schema_geo or schema_geoshape — trail markers
  - TouristTrip nodes — destination polylines

seanmontague_map.module:
  hook_page_attachments(): pass layer definitions to drupalSettings.seanMap
  with layer toggle config per content type.

src/Controller/SeanMapController.php:
  Aggregates all four data sources into a single GeoJSON FeatureCollection.
  Each feature carries: type (place/poi/article/trip), title, teaser,
  image_url, node_url, lat, lon, color.

templates/:
  map-popup--place.html.twig    — image, title, address, link
  map-popup--poi.html.twig      — image, title, description, link
  map-popup--article.html.twig  — image, title, category, distance, link
  map-popup--trip.html.twig     — image, title, date range, places count, link

js/seanmontague-map.js:
  Layer toggle UI (trails/places/pois/trips checkboxes)
  Color coding per content type
  Surface-styled popups matching the design system

Show all files before writing.
After: lando drush en seanmontague_map && lando drush cr
```

---

## Step 4 — elevation_unit config sync (PENDING)

```
Read the root CLAUDE.md.

Check public_html/modules/custom/trail_mapper/config/install/trail_mapper.settings.yml
and config/sync/trail_mapper.settings.yml.

If elevation_unit is missing from config/sync run:
  lando drush cex
  git add config/sync/trail_mapper.settings.yml
  git commit -m "Export elevation_unit to config sync"

Verify hook_page_attachments in trail_mapper.module passes elevationUnit
to drupalSettings (it does — confirm only, no change expected).
```

---

## URL Aliases

Set manually on each node. Convention:

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

0e → 0f-i → 0f-ii → 0g-i → 0g-ii → 1a → 1b → 0g-iii → 0g-iv →
2a → 2b → 3a → 3b → 4

Steps 0g-iii and 0g-iv are optional and depend on Step 1 completing first.
