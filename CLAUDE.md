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
lando npm run watch           # dev mode: Vite + Storybook on localhost:6007
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
| `leaflet_full_page` | Full-page Leaflet mapping with custom paragraph types |
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
| `field_map_tiles` | list (text) | Per-article tile set override (optional) — for non-US content |
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
| `body` | text_with_summary | Trip overview |
| `schema_date_published` | datetime | Fallback date |
| `field_trip_dates` | Smart Date (cardinality 1) | Trip date range — start + end |
| `schema_destination` | entity_reference → place[] | Places visited (multi-value, ordered) |
| `schema_itinerary` | entity_reference → article[] | Articles under this trip |
| `schema_image` | entity_reference → ImageObject | Hero image |
| `field_editorial` | entity_reference_revisions | Editorial tracking |

### Place fields

| Field | Type | Purpose |
|---|---|---|
| `body` | text_with_summary | Place description |
| `schema_latitude` | decimal | Latitude — map center |
| `schema_longitude` | decimal | Longitude — map center |
| `schema_address` | address | Structured address |
| `schema_image` | entity_reference → ImageObject | Place photo |
| `schema_telephone` | string | Phone |

### Geo field access — IMPORTANT

Two different geo patterns are used. Never mix them up:

**Article** — `schema_geo` is a **Geofield**. Access as:
```twig
{% set has_geo = not node.schema_geo.isEmpty() %}
{% set map_center = node.schema_geo.lat ~ ',' ~ node.schema_geo.lon %}
```

**Place** — uses separate **decimal fields** `schema_latitude` and
`schema_longitude`. There is no `schema_geo` on Place. Access as:
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

The common mistake is `place.schema_geo.value.lat` — Place has no `schema_geo`,
so `has_place` silently evaluates false and the map center fallback never works.

### Taxonomy vocabularies

| Vocabulary | machine name | Notes |
|---|---|---|
| Category | `category` | trails / drupal / permaculture / maps — needs `field_key` added |
| Activity Type | `activity_type` | bike / hike / ski — needs `field_key` added |
| Tags | `tags` | general tagging |

`field_key` is a plain text field (max 32 chars) on taxonomy terms used by
the Pathauto hook to generate URL path segments. **It does not exist yet.**

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

## Pending Work

See `claude-code-next-steps.md` for full prompts. Remaining items:

- **0e** — `field_trip_dates` Smart Date (cardinality 1) on tourist_trip;
  update `node--trip.html.twig` to render date range
- **0f** — `field_map_tiles` select field on article; register tile sets
  in trail_mapper; wire override through `node--article.html.twig` and `map.js`
- **2** — Add `field_key` (text plain, max 32) to `category` and
  `activity_type` vocabularies; populate term keys
- **4** — Add `elevation_unit` to `trail_mapper.settings` schema +
  install config + `hook_page_attachments`
- **5** — `hook_pathauto_pattern_alter()` for conditional URL aliases
  (blocked on step 2)

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
