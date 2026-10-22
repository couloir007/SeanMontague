# Custom modules: current state

Snapshot of `public_html/modules/custom/` as of 2026-10-22. It describes what the code does today, not what it should do. Refresh it when a module changes. Seven modules, all `package: Sean Montague`, all `core_version_requirement: ^10 || ^11`. For JSON-LD hooks and gotchas, see `public_html/modules/custom/seanmontague_schemadotorg/seanmontague_schemadotorg-README.md`. For the KMZ importer, see `public_html/modules/custom/trip_import/CLAUDE.md`.

This is a summary. Read a module's files before changing them.

## Where new map/geo code goes
- Processes tracks, generates GeoJSON, or computes elevation or distance: `trail_mapper`. It's the reusable engine and knows nothing about this site's content model.
- Decides which of the site's entities or fields become markers or feed the map UI: `seanmontague_map`. It's the site-specific aggregation layer.
- Both outputs meet in the theme: the article preprocess hook calls `ArticleGeoData` and `ArticleMapData` and composes `has_map`. `ArticleMapData` also serves page nodes through `buildPage()`, so read "Article" in its name as "node."
- This section replaces the deleted `MODULES-trail_mapper-vs-seanmontague_map.md`. The reasoning is in `docs/architecture/decisions.md` ("Two map services, split by responsibility").

## Inventory

| Module | Declared dependencies | Services | Routes | Hooks |
|---|---|---|---|---|
| `trail_mapper` | none declared | `trail_mapper.geo_shape_converter`, `trail_mapper.article_geo_data` | `/trail-mapper/generate-geojson` (access content), `/admin/config/trail-mapper` | `entity_presave`, `media_presave`, `page_attachments` |
| `seanmontague_map` | node, serialization, geo_entity | `seanmontague_map.article_map_data` | `/sean-map/items` (access content) | `theme`, `page_attachments` |
| `seanmontague_schemadotorg` | node, schemadotorg, schemadotorg_jsonld, geo_entity | none | none | `schemadotorg_jsonld_alter`, `schemadotorg_jsonld_schema_type_entity_alter` |
| `trip_import` | file, node, media | none | `/admin/config/trip-import` | none (form + batch) |
| `map_page` | field, views, geofield, taxonomy, datetime, text, paragraphs, serialization, geo_entity | `map_page.map_items` | `/map-page/map-items/{bundle}` and `/map-page/map-items` (access content), `/admin/config/map-page/settings` | `theme`, `preprocess_html`, `theme_suggestions_views_view_alter`, `form_alter`, `views_pre_render`, `install`, `uninstall` |
| `external_pg` | none | `external_pg.service` | none | none |
| `trailmapper_safeguards` | menu_link_content | none | none | `module_implements_alter`, `menu_link_content_presave`, `node_presave`, `entity_presave` |

## Per module

**`trail_mapper`**: GeoJSON/GPX engine. `GeoShapeConverter` converts `data_download` media files to GeoJSON on media presave and re-stamps `route_type` when only `field_route_type` changes; on node presave it handles waypoint-to-POI extraction and the `schema_geoshape` re-set. `GeoElevationCalculator` computes noise-filtered gain/loss with resampling. `ArticleGeoData` builds article track/stats display data. `page_attachments` sends tile config, elevation unit, and `route_type` term styles to `drupalSettings.trailMapper` on every page. Settings: `tile_key: usgs-topo`, `elevation_unit: feet`, `extract_waypoints: false`. Has the repo's only test (`tests/Unit/GeoElevationCalculatorTest.php`, fixture `mtb_ride.gpx`).

**`seanmontague_map`**: site-specific marker aggregation. `ArticleMapData::build()` (articles) and `buildPage()` (page/front). `SeanMapController::items()` serves Places, POIs, Trail Articles, and Trips as one feed. `page_attachments` attaches its library and `drupalSettings.seanMap` layer definitions on every page. Popup templates for article, place, poi, trip.

**`seanmontague_schemadotorg`**: JSON-LD enrichment and correction. Classes in `src/JsonLd/`: `ArticleJsonLd`, `TouristTripJsonLd`, `PointOfInterestJsonLd`, `LodgingJsonLd`, `PersonJsonLd`. Helpers in the `.module` for `isPartOf`, ISO date normalization, `copyrightYear`, and URL-key normalization. Has a README.

**`trip_import`**: Google My Maps KMZ importer creating `geo_entity:poi`, `geo_entity:lodging`, `node:tourist_destination`, one `node:article` per Day folder, and `node:tourist_trip`. Has its own `CLAUDE.md`.

**`map_page`**: older full-page Leaflet map. Ships config in `config/install`: a `geo_entity:map_story` bundle with fields, three paragraph types (`map_field_link`, `map_full_text`, `map_related_content`), a `mapping` vocabulary, and the `map_page` view. Its install hook enables a Views display extender and creates a "Map Story" term. Uses jQuery and `once` (module JS, not Surface). Has a README.

**`external_pg`**: one service that registers an `external_pg` pgsql connection to the `TrailMapper` database and exposes `fetchData($query)` for raw SQL.
Status: deprecated. Direct DB link to TrailMapper, to be removed once the TrailMapper API client exists (see `decisions.md`).

**`trailmapper_safeguards`**: forces itself to run last on menu link, node, and entity presave. Validates `menu_name`. On every node save, in both `node_presave` and `entity_presave`, it calls `setPublished(TRUE)` and fills empty status and title defaults.

## Known problems (from reading the code, not from running it)

1. **`external_pg` credentials are hardcoded** in `ExternalPgService.php` (database, user, password, host, port), and the host is a private LAN address. The constructor also `echo`es a "Connected to..." line with that address, which would print into any response that instantiates the service.
2. **`/trail-mapper/generate-geojson` is broken.** `GeoJsonController::create()` requests `trail_mapper.geojson_generator`, which isn't registered in `trail_mapper.services.yml`, so the route should throw a service-not-found error. The route is open to anyone with `access content`. `GeoJsonGenerator` depends on `external_pg`, but `trail_mapper.info.yml` doesn't declare that dependency. The old inline version of the generator is commented out in `trail_mapper.module`. `GeoJsonGenerator` and its route go away with `external_pg`.
3. **`trailmapper_safeguards` publishes every node on save.** Nothing can be saved unpublished, which blocks drafts and conflicts with `trailmapper-interface.md`'s plan to keep generated routes unpublished. Its info.yml description mentions only menu links.
4. **`KingdomTrails.geojson` (825 KB) is duplicated**, identical in `trail_mapper/includes/` and `map_page/includes/`. Only `GeoJsonGenerator` references a path to it. This is TrailMapper-owned trail data stored in the Drupal repo.
5. **Two marker GeoJSON endpoints overlap**: `/map-page/map-items/{bundle}` (`map_page`) and `/sean-map/items` (`seanmontague_map`).
6. **Two modules attach to every page**: `trail_mapper` (drupalSettings, including a `route_type` term load) and `seanmontague_map` (library and drupalSettings), regardless of whether the page has a map.
7. **`trail_mapper.info.yml`** still describes the module as "Generates GeoJSON from external PostgreSQL data" and declares no dependencies, though it uses media, file, taxonomy, and `external_pg`.
8. **`map_page` installs field storage and bundles from `config/install`.** On a config-managed site that's harmless after the first install, but it duplicates what `config/sync` owns.
