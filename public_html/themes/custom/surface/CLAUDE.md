# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working
with code in this repository.

# SeanMontague.com — Project Root

## Project Overview

Personal site for Sean Montague (seanmontague.com) — covering Kingdom Trails
mountain biking, Burke Mountain skiing, permaculture/food forest, and
Leaflet-based interactive mapping. Built on Drupal 10/11 with a custom theme
(Surface) and custom geospatial modules. Sean is a web developer at the
Smithsonian NMNH. This is a personal outlet — not a portfolio or consulting site.

## Environment

- **Local:** Lando (Pantheon recipe) — `lando start` to start services
- **Hosting:** Pantheon
- **PHP:** 8.3, **Database:** PostgreSQL 15 with PostGIS
- **URL:** https://seanmontague.lndo.site

Always prefix PHP/Drupal/frontend commands with `lando` — never run `drush`,
`composer`, or `npm` directly on the host.

## Common Commands

```bash
# Start local environment
lando start
lando info                    # show URLs and credentials

# Drupal
lando drush cr                # clear cache
lando drush cim               # import config
lando drush cex               # export config

# Composer
lando composer install
lando composer require drupal/module_name

# Frontend — run from theme directory via lando ssh, or:
lando npm run build           # full production build (lint + vite)
lando npm run watch           # dev mode: Vite + Storybook on localhost:6006
lando npm run lint:fix        # Biome JS/TS auto-fix
lando npm run stylelint:fix   # CSS auto-fix

# Tests
lando php public_html/modules/custom/trail_mapper/tests/Unit/GeoElevationCalculatorTest.php
lando php vendor/bin/phpunit public_html/modules/custom
```

## Directory Structure

- **Web root:** `public_html/`
- **Custom theme:** `public_html/themes/custom/surface/`
- **Custom modules:** `public_html/modules/custom/`
- **Config sync:** `config/sync/`
- **Composer:** `composer.json` at project root

## Custom Modules

| Module | Purpose |
|---|---|
| `trail_mapper` | GeoJSON generation from external PostgreSQL DB; TrailMapper settings form at `/admin/config/trail-mapper`; `GeoShapeConverter` (GPX/GeoJSON); `GeoElevationCalculator` (shelved) |
| `external_pg` | Service layer (`ExternalPgService`) for external PostgreSQL TrailMapper DB — credentials hardcoded in `ExternalPgService.php` |
| `geo_content_builder` | Custom entities for plotting geographic content on Leaflet maps |
| `leaflet_full_page` | Generic full-page Leaflet mapping. Configurable GeoJSON endpoint at `/leaflet-full-page/map-items/{bundle}`. Settings at `/admin/config/leaflet-full-page/settings`. EDAN/NMNH specifics removed. |
| `trailmapper_safeguards` | Prevents invalid `menu_name` on MenuLinkContent entities |
| `global_volcanism` | Smithsonian NMNH GVP integration |
| `gvp_external_resources` | GVP external resources views/fields |
| `custom_entity` | Placeholder/scaffolding — incomplete (`@todo`) |

## Coding Standards

- **Drupal:** [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards)
- **CSS:** BEM (Block Element Modifier)
- **JavaScript:** ES6+; all JS files must begin with `/* jshint esversion: 6 */`
- **Theme:** Modified Atomic Design — Base → Elements → Components → Collections → Layouts → Pages
- **Twig:** Use namespaces (`@components`, `@elements`, etc.); never `@components/surface/`

---

## Content Model

### Geo entity model

| Type | Bundle | Schema.org | Purpose |
|---|---|---|---|
| `geo_entity` | `poi` | `TouristAttraction` | Attractions, landmarks, features — map markers ✅ |
| `geo_entity` | `destination` | `Place` | Trip stop anchors — Dublin, Galway etc ❌ pending |
| `node` | `place` | `Place` | Content hub landing pages — Kingdom Trails, Burke Mountain |

`geo_entity:destination` is referenced by TouristTrip `schema_destination`.
`geo_entity:poi` is referenced by article `schema_poi`.
`node:place` is referenced by article `schema_place` as a content hub.

### Single article bundle

All written content uses a single `article` (BlogPosting) bundle.
`schema_category` and `schema_activity_type` taxonomy drives display and
navigation. There is no separate `trail_report` bundle.

### Article (BlogPosting) fields

| Field | Type | Purpose |
|---|---|---|
| `body` | text_with_summary | Article body |
| `schema_date_published` | datetime | Publication date |
| `schema_category` | entity_reference → category | trails / drupal / permaculture / maps |
| `schema_activity_type` | entity_reference → activity_type | bike / hike / ski |
| `schema_trip` | entity_reference → tourist_trip | Parent trip (optional) |
| `schema_place` | entity_reference → place | Map center fallback (optional) |
| `schema_geo` | geofield | Direct lat/lon map center fallback (optional) |
| `field_map_tiles` | list (text) | Per-article tile set override — for non-US content |
| `schema_geoshape` | file | GeoJSON/GPX track — drives map + elevation profile |
| `schema_distance` | decimal | Miles (manual — display fallback when no geoshape) |
| `schema_elev_gain` | integer | Feet gain (manual) |
| `schema_elev_loss` | integer | Feet loss (manual) |
| `schema_elev_min` | integer | Feet min elevation (manual) |
| `schema_elev_max` | integer | Feet max elevation (manual) |
| `schema_difficulty` | list | Easy / Intermediate / Hard / Expert |
| `schema_audio` | entity_reference → AudioObject | ElevenLabs TTS (optional) |
| `field_image` | entity_reference → ImageObject | Hero image |

Stat fields (distance, elev_*) are optional — relevant only for trail/ski
articles. Stats bar renders from Twig manual fields when schema_distance is
set and schema_geoshape is absent.

### TouristTrip fields

| Field | Type | Purpose |
|---|---|---|
| `body` | text_with_summary | Trip narrative |
| `field_trip_dates` | Smart Date (cardinality 1) | Trip date range — start + end |
| `schema_destination` | entity_reference → geo_entity:destination[] | Trip stop anchors (multi-value, ordered) |
| `schema_itinerary` | entity_reference → article[] | Articles under this trip (ordered) |
| `schema_image` | entity_reference → ImageObject | Hero image |
| `field_editorial` | entity_reference_revisions | Editorial tracking |

**Date fields note:** Smart Date (`schema_event_schedule`) was removed.
Schema.org Blueprints cannot map a single field to both `departureTime`
and `arrivalTime`, so two separate plain Date fields are used instead.
`schema_date_published` remains on the bundle as a fallback but is
superseded by `field_departure_date` / `field_arrival_date`.

**Template date range pattern:**
```twig
{% set depart = node.field_departure_date.value %}
{% set arrive = node.field_arrival_date.value %}
{% if depart and arrive %}
  {% set date_display = depart|date('F j') ~ '–' ~ arrive|date('F j, Y') %}
{% elseif depart %}
  {% set date_display = depart|date('F j, Y') %}
{% else %}
  {% set date_display = node.schema_date_published.value|date('F j, Y') %}
{% endif %}
```

### Place fields

| Field | Type | Purpose |
|---|---|---|
| `body` | text_with_summary | Place description |
| `schema_latitude` | decimal | Latitude — map center |
| `schema_longitude` | decimal | Longitude — map center |
| `schema_address` | address | Structured address (country required inline) |
| `schema_image` | entity_reference → ImageObject | Place photo |
| `schema_telephone` | string | Phone |

### Geo field access — IMPORTANT

Two different geo patterns are used. Never mix them up:

**Article** — `schema_geo` is a **Geofield**. Access as:
```twig
{% set has_geo = not node.schema_geo.isEmpty() %}
{% set map_center = node.schema_geo.lat.value ~ ',' ~ node.schema_geo.lon.value %}
```

`schema_geo.lat` and `schema_geo.lon` return `FloatData` TypedData objects, **not**
raw PHP floats. Always append `.value` before using in string concatenation (`~`),
`json_encode`, or any other string context.

**Place** — uses separate **decimal fields** `schema_latitude` and
`schema_longitude`. No Geofield on Place. Access as:
```twig
node.schema_latitude.value
node.schema_longitude.value
```

**Reading Place geo from a referenced Place on an article:**
```twig
{% set place = node.schema_place.entity %}
{% set has_place = place and place.schema_latitude.value is not empty %}
{% set map_center = place.schema_latitude.value ~ ',' ~ place.schema_longitude.value %}
```

The common mistake is `place.schema_geo.value.lat` — Place has no
`schema_geo`, so `has_place` silently evaluates false.

**geo_entity (poi / destination) — Geofield + label:**
```twig
{# lat/lon: always .value #}
'lat': poi.schema_geo.lat.value,
'lon': poi.schema_geo.lon.value,

{# label: geo_entity defines "label" = "label" in entity keys, so .label returns
   a FieldItemList, not a string. Always use .label.value #}
'label': '<strong>' ~ poi.label.value ~ '</strong>',
```

**schema_geoshape file entity — filename:**
```twig
{# filename returns a FieldItemList, not a string. Always use .value for matches/string ops #}
{% set is_gpx = geo_file and geo_file.filename.value matches '/\\.gpx$/i' %}
```

### Taxonomy vocabularies

| Vocabulary | machine name | Notes |
|---|---|---|
| Category | `category` | trails / drupal / permaculture / maps — needs `field_key` added |
| Activity Type | `activity_type` | bike / hike / ski — needs `field_key` added |
| Tags | `tags` | general tagging |

`field_key` is a plain text field (max 32 chars) on taxonomy terms.
**Does not exist yet** — needed for URL alias conventions.

### route_type vocabulary (pending)

| Term | Key | Map line style |
|---|---|---|
| Driving | `driving` | `--muted` dashed |
| Walking | `walking` | `--forest` solid |
| Hiking | `hiking` | `--trail` solid |
| Cycling | `cycling` | `--sky` solid |

Field `field_route_type` (entity_reference → route_type) on article bundle.
Drives map line color/dash styling in seanmontague_map and map.js.

---

## Mapping

All Leaflet rendering is handled by `map.js` in the Surface theme.

- **Never** use the Drupal Leaflet module formatter on article pages —
  `schema_geo` must be **hidden** in the article view display to prevent
  a rogue second map from rendering
- Default tiles: USGS National Map (US only, no API key)
- Per-article tile override: `field_map_tiles` — use for non-US content
- Available tile keys: `usgs-topo`, `osm`, `open-topo`, `esri-topo`
- GeoJSON Z values stored in **meters** by GeoShapeConverter; converted to
  display unit client-side via `drupalSettings.trailMapper.elevationUnit`
- After init: `window._surfaceMaps[map_id]`, `window._surfaceTracks[map_id]`,
  `surface-map-ready` CustomEvent `{ map_id, map, coords }`

---

## URL Aliases

Set manually on each node. No Pathauto automation.

| Content | Pattern |
|---|---|
| Trail report | `/trails/bike/[title]`, `/trails/hike/[title]`, `/trails/ski/[title]` |
| Trip | `/trips/[title]` |
| Trip article | `/trips/[trip-title]/[article-title]` |
| Drupal post | `/drupal/[title]` |
| Permaculture post | `/permaculture/[title]` |
| Writing | `/writing/[title]` |

---

## Pending Work

See `claude-code-next-steps.md` for full prompts. Remaining items:

- **0e** — Add `field_departure_date` + `field_arrival_date` to tourist_trip;
  update `node--trip.html.twig` to render date range
- **0f** — `field_map_tiles` on article; register tile sets in trail_mapper;
  wire through template and map.js
- **0g** — GPX: DataDownload media type, waypoint POI extraction,
  Schema.org spatialCoverage track output
- **1** — geo_entity `poi` bundle + `seanmontague_schemadotorg` module
- **2** — Add `field_key` to `category` and `activity_type` taxonomies
- **3b** — seanmontague_map extension module (3a complete — leaflet_full_page refactored)

Bolt → Surface theme integration (Steps A–D complete):
- Tokens, CSS tweaks, drop cap, reading progress, map-link-list — done
- New components: dest-index, dest-timeline (destination strip + itinerary)
- Step E pending: node--trip.html.twig redesign using dest-index +
  dest-timeline + ireland-2024.html layout reference

---

## Schema.org Content Type Creation Order

Create types in dependency order — dependencies must exist before dependents:

```bash
lando drush schemadotorg:create-type taxonomy_term:DefinedTerm
lando drush schemadotorg:create-type media:ImageObject
lando drush schemadotorg:create-type media:AudioObject
lando drush schemadotorg:create-type node:Person
lando drush schemadotorg:create-type node:Place
lando drush schemadotorg:create-type node:BlogPosting
lando drush schemadotorg:create-type node:TouristTrip
lando drush schemadotorg:create-type node:Event
```

---

## Config Management

Always export after UI changes before committing:

```bash
lando drush cex
git add config/sync
git commit -m "Export config for [feature]"
```

Always import on environment setup or after pulling:

```bash
lando drush cim && lando drush cr
```

## Git Workflow

- Do not commit: `public_html/core`, `public_html/modules/contrib`,
  `public_html/themes/contrib`
- Config changes (`config/sync`) should be committed with the feature
  that requires them
- Do not edit `settings.php` directly — local overrides go in `settings.lando.php`

## Theme Reference

See `public_html/themes/custom/surface/CLAUDE.md` for full theme architecture,
Twig rules, content model, component patterns, JS standards, and Leaflet patterns.

When working on anything inside the Surface theme, also read
`public_html/themes/custom/surface/STORYBOOK.md` before making any changes.
It contains critical rules for avoiding Storybook breakage — scope restrictions,
JS dual-context patterns, Twig namespace rules, and common failure modes.
