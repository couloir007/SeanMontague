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

## Related Documentation

| Doc | Path | Scope |
|---|---|---|
| **This file** | `CLAUDE.md` | Stack, repo layout, quick-start |
| **Schema.org Blueprints** | `SCHEMADOTORG.md` | Content types, field naming, shared storages, JSON-LD |
| **Trail journal guidelines** | `public_html/themes/custom/surface/CLAUDE.md` | Writing voice, NH 48, biking reports |
| **Surface theme** | `public_html/themes/custom/surface/CLAUDE.md` | Drupal templates, preprocess, content model, Leaflet |
| **Surface source** | `public_html/themes/custom/surface/source/CLAUDE.md` | Design-system source (CSS/JS/tokens/Storybook) |
| **Storybook rules** | `public_html/themes/custom/surface/STORYBOOK.md` | JS dual-context, Twig rules, Storybook failure modes |
| **Map/geo module split** | `public_html/modules/custom/MODULES-trail_mapper-vs-seanmontague_map.md` | Which map/geo module owns what |
| **JSON-LD module** | `public_html/modules/custom/seanmontague_schemadotorg/README.md` | JSON-LD hooks, JsonLd classes, normalizers, CollectionPage plan |

Read `SCHEMADOTORG.md` before creating any content type, field, or JSON-LD
customisation. Read `STORYBOOK.md` before touching anything in `source/`.

---

## Stack

- **CMS:** Drupal 10/11 (PHP 8.3, Composer, Drush)
- **Hosting:** Pantheon (no frontend build step — `dist/` is committed)
- **Local dev:** Lando (Pantheon recipe) — `lando start` to start services
- **Database:** PostgreSQL 15 with PostGIS
- **Theme:** Surface — Storybook-driven pattern library, Vite build
- **Content model:** Schema.org Blueprints (`drupal/schemadotorg`)
- **Mapping:** Leaflet + GeoJSON, custom `trail_mapper`, `seanmontague_map`, and `map_page` modules
- **URL:** https://seanmontague.lndo.site

Always prefix PHP/Drupal/frontend commands with `lando` — never run `drush`,
`composer`, `npm`, or `terminus` directly on the host.

---

## Repo Layout

```
/
├── CLAUDE.md                          # ← you are here
├── SCHEMADOTORG.md                    # Schema.org Blueprints reference
├── composer.json / .lock              # Drupal + PHP dependencies
├── config/                            # Drupal config export (drush cex/cim)
├── public_html/
│   ├── modules/custom/
│   │   ├── MODULES-trail_mapper-vs-seanmontague_map.md  # map/geo module split
│   │   ├── trail_mapper/              # GeoJSON/GPX engine, elevation, ArticleGeoData
│   │   ├── seanmontague_map/          # Site-specific map aggregation, ArticleMapData
│   │   ├── seanmontague_schemadotorg/ # JSON-LD enrichment + correction (has README)
│   │   ├── trip_import/               # KMZ → geo entities + trip content importer
│   │   ├── map_page/                  # Full-page Leaflet map + GeoJSON endpoint
│   │   ├── external_pg/               # External PostgreSQL service layer
│   │   └── trailmapper_safeguards/    # MenuLinkContent validation
│   └── themes/custom/
│       └── surface/                   # The theme
│           ├── CLAUDE.md              # Theme architecture + writing guidelines
│           ├── STORYBOOK.md           # Storybook rules — read before touching source/
│           ├── includes/              # *.theme partials (node.theme, html.theme, views.theme)
│           ├── source/                # Design system source (Vite + Storybook)
│           │   ├── CLAUDE.md          # Source-directory reference
│           │   ├── props/             # Design tokens (nek.css)
│           │   └── patterns/          # elements/ → components/ → collections/ → layouts/
│           ├── dist/                  # Vite build output (committed for Pantheon)
│           └── templates/             # Drupal twig overrides
└── upstream-configuration/            # Pantheon upstream
```

---

## Quick Start (local dev)

```bash
composer install
lando start
lando db-import snapshot.sql.gz       # latest DB snapshot
lando drush cim -y                    # import config
lando drush cr                        # clear cache

# Theme development
cd public_html/themes/custom/surface
lando npm install
lando npm run watch                   # Storybook + Vite dev server (localhost:6006)
lando npm run build                   # compile source → dist/

# Tests
lando php public_html/modules/custom/trail_mapper/tests/Unit/GeoElevationCalculatorTest.php
lando php vendor/bin/phpunit public_html/modules/custom
```

---

## Deployment Workflow

Pantheon has no frontend build step. The compiled `dist/` must be committed:

```bash
# After any source/ changes:
cd public_html/themes/custom/surface
lando npm run build
cd /path/to/repo/root
git add public_html/themes/custom/surface/dist/
git commit -m "Rebuild dist"
git push
```

---

## Common Commands

```bash
# Drupal
lando drush cr                    # clear cache
lando drush cim                   # config import
lando drush cex                   # config export
lando drush schemadotorg:create-type node:Place

# Composer
lando composer require drupal/module_name
lando composer install

# Frontend (from theme directory)
lando npm run build
lando npm run watch
lando npm run lint:fix             # Biome JS/TS auto-fix
lando npm run stylelint:fix        # CSS auto-fix

# Pantheon
lando terminus env:deploy seanmontague.dev
lando terminus drush seanmontague.dev -- cr
```

---

## Content Model (overview)

All content types use Schema.org Blueprints. Fields use `schema_` prefix for
schema-mapped fields, `field_` for editorial-only. See `SCHEMADOTORG.md` for
the full reference — especially the shared storage rule.

### Single article bundle

All written content uses a single `article` (BlogPosting) bundle.
`schema_category` and `schema_activity_type` taxonomy drives display and
navigation. There is no separate `trail_report` bundle.

### Content Types (node bundles)

| Bundle | Schema.org type | Purpose |
|---|---|---|
| `article` | `BlogPosting` | All trail reports, writing, Drupal, permaculture posts |
| `tourist_trip` | `TouristTrip` | Multi-destination travel posts |
| `tourist_destination` | `Place` | Trip stop anchors referenced by a trip |
| `place` | `Place` | Content hub landing pages (Kingdom Trails, Burke Mountain) |
| `person` | `Person` | Author/about entity; homepage About section via `schema_main_entity` |
| `event` | `Event` | Kingdom Trails events, group rides, clinics |
| `event_series` | `EventSeries` | Recurring events |
| `page` | `WebPage` | Homepage + static/landing pages (About fields, hero map, CTAs) |
| `web_site` | `WebSite` | Site-level structured data, authorship anchor |
| `podcast_episode` / `podcast_series` | `PodcastEpisode` / `PodcastSeries` | Audio (planned, ElevenLabs) |

### Geo Entity Model

| Type | Bundle | Schema.org | Purpose |
|---|---|---|---|
| `geo_entity` | `poi` | `TouristAttraction` | Map markers — attractions, landmarks, features |
| `geo_entity` | `lodging` | `LodgingBusiness` | Lodging markers (brown pins) |
| `node` | `tourist_destination` | `Place` | Trip stop anchors (Dublin, Galway, etc.) |
| `node` | `place` | `Place` | Content hub landing pages |

### Taxonomy

| Vocabulary | machine name | Notes |
|---|---|---|
| Category | `category` | trails / drupal / permaculture / maps |
| Activity Type | `activity_type` | bike / hike / ski |
| Tags | `tags` | general tagging |
| Route Type | `route_type` | driving / walking / hiking / cycling — drives map line style |

### Schema.org Content Type Creation Order

Always create in dependency order:

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

## Custom Modules

| Module | Purpose |
|---|---|
| `trail_mapper` | GeoJSON/GPX engine: `GeoShapeConverter`, `GeoElevationCalculator`, `ArticleGeoData` (article track/stats display data). Settings at `/admin/config/trail-mapper`. Reusable, site-agnostic. |
| `seanmontague_map` | Site-specific map aggregation: Places/POIs/Lodging + node refs → markers and a unified GeoJSON feed. `ArticleMapData` (markers + map config for article & page nodes). See `MODULES-trail_mapper-vs-seanmontague_map.md`. |
| `seanmontague_schemadotorg` | JSON-LD enrichment + correction over Schema.org Blueprints (mentions, trip times, knowsAbout, geo on referenced entities; `@url`→`url` + ISO-date normalizers). See module README. |
| `trip_import` | Admin UI to import Google My Maps KMZ → geo_entity records, GeoJSON route files, and trip content (day articles, destinations, tourist_trip). |
| `map_page` | Full-page Leaflet map with custom paragraph types; GeoJSON endpoint at `/map-page/map-items/{bundle}`. |
| `external_pg` | Service layer (`ExternalPgService`) for the external PostgreSQL TrailMapper DB. |
| `trailmapper_safeguards` | Project-level data-integrity safeguards (e.g. invalid `menu_name` on MenuLinkContent). |

---

## Node Preprocess & Data Services

Node templates are **pure markup**. Data-shaping lives in bundle / view-mode
preprocess hooks in `public_html/themes/custom/surface/includes/node.theme`,
which delegate to services. There is **no base `surface_preprocess_node()`** —
each hook fires because its `node--*.html.twig` template registers the theme
hook (Drupal auto-attaches `surface_preprocess_{hook}` when it exists). Add a new
one by creating the template, then the matching
`surface_preprocess_node__{suggestion}()`.

| Hook | Template | Builds |
|---|---|---|
| `surface_preprocess_node__article()` | `node--article.html.twig` | geo/track/stats + markers + unified `has_map` + `article_header` |
| `surface_preprocess_node__page__front()` | `node--page--front.html.twig` | homepage hero map + categories strip |
| `surface_preprocess_node__person__front()` | `node--person--front.html.twig` | About-section `about` var |

The article hook calls both data services and composes their output —
`trail_mapper.article_geo_data` (`ArticleGeoData`, track/geo/stats) and
`seanmontague_map.article_map_data` (`ArticleMapData`, markers + map config) —
then computes the unified `has_map` (any geo source **or** any marker, so a
marker-only article still gets a map). See the map/geo module split doc for which
module owns what.

**Geofield accessor (verified):** `entity.schema_geo.lat` / `.lon` (Twig) or
`$e->get('schema_geo')->lat` / `->lon` (PHP) returns the float **directly**.
`.lat.value` and `.0.value.lat` are NULL.

**Homepage About:** the About content is a `person` node (`body` → bio,
`schema_knows_about` → skills) referenced via the page's `schema_main_entity`,
rendered in the **`front`** view mode. Framing (`field_about_label` /
`field_about_title`) lives on the `page`, so the person hook reads those off the
routed page node.

---

## Mapping

All Leaflet rendering is handled by `map.js` in the Surface theme.

- **Never** use the Drupal Leaflet module formatter on article pages —
  `schema_geo` must be **hidden** in the article view display to prevent
  a second map from rendering
- Default tiles: USGS National Map (US only, no API key, feet units)
- Per-article tile override: `field_map_tiles` — use for non-US content
- Available tile keys: `usgs-topo`, `osm`, `open-topo`, `esri-topo`
- After init: `window._surfaceMaps[map_id]`, `window._surfaceTracks[map_id]`,
  `surface-map-ready` CustomEvent fires with `{ map_id, map, coords }`
- Event key is `map_id` (snake_case), not `mapId`

---

## URL Aliases

Set manually on each node. No Pathauto automation.

| Content | Pattern |
|---|---|
| Trail article | `/trails/bike/[title]`, `/trails/hike/[title]`, `/trails/ski/[title]` |
| Trip | `/trips/[title]` |
| Trip article | `/trips/[trip-title]/[article-title]` |
| Drupal post | `/drupal/[title]` |
| Permaculture post | `/permaculture/[title]` |
| Writing | `/writing/[title]` |

---

## Coding Standards

- **Drupal:** [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards)
- **CSS:** BEM (Block Element Modifier)
- **JavaScript:** ES6+; all JS files must begin with `/* jshint esversion: 6 */`
- **Theme:** Modified Atomic Design — Base → Elements → Components → Collections → Layouts → Pages
- **Twig:** Use namespaces (`@components`, `@elements`, etc.); never `@components/surface/`

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

---

## Git Workflow

- Sean handles all git operations
- Do not commit: `public_html/core`, `public_html/modules/contrib`,
  `public_html/themes/contrib`
- Config changes (`config/sync`) should be committed with the feature
  that requires them — never separately unless a config-only change
- Do not edit `settings.php` directly — local overrides go in `settings.lando.php`

---

## Working Rules

- **Read the relevant docs before working.** Token values, field schemas, and
  component patterns change. Don't work from memory.
- **No programmatic `hook_install()` field creation** — it conflicts with
  config-managed fields. All fields via Blueprints UI or Structure → Manage fields.
- **Config is managed via `drush cex` / `drush cim`.** Export after every UI change.
- **Sean handles all git operations.** Never commit, push, or manage branches.
