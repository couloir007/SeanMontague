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
| **Storybook rules** | `public_html/themes/custom/surface/STORYBOOK.md` | JS dual-context, Twig rules, Storybook failure modes |

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
- **Mapping:** Leaflet + GeoJSON, custom `trail_mapper` and `leaflet_full_page` modules
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
│   │   ├── trail_mapper/              # GeoJSON/GPX, elevation, geo utilities
│   │   ├── external_pg/               # External PostgreSQL service layer
│   │   ├── geo_content_builder/       # Geographic content entities
│   │   ├── leaflet_full_page/         # Full-page Leaflet map module
│   │   └── trailmapper_safeguards/    # MenuLinkContent validation
│   └── themes/custom/
│       └── surface/                   # The theme
│           ├── CLAUDE.md              # Theme architecture + writing guidelines
│           ├── STORYBOOK.md           # Storybook rules — read before touching source/
│           ├── source/                # Design system source (Vite + Storybook)
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

### Content Types

| Bundle | Schema.org type | Purpose |
|---|---|---|
| `article` | `BlogPosting` | All trail reports, writing, Drupal, permaculture posts |
| `trip` | `TouristTrip` | Multi-destination travel posts |
| `place` | `Place` | Content hub landing pages (Kingdom Trails, Burke Mountain) |
| `event` | `Event` | Kingdom Trails events, group rides, clinics |
| `event_series` | `EventSeries` | Recurring events |
| `web_page` | `WebPage` | Static pages (About, Contact) |
| `web_site` | `WebSite` | Site-level structured data, authorship anchor |

### Geo Entity Model

| Type | Bundle | Schema.org | Purpose |
|---|---|---|---|
| `geo_entity` | `poi` | `TouristAttraction` | Map markers — attractions, landmarks, features |
| `geo_entity` | `destination` | `Place` | Trip stop anchors (Dublin, Galway, etc.) |
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
| `trail_mapper` | GeoJSON generation from external PostgreSQL DB; `GeoShapeConverter` (GPX/GeoJSON); settings at `/admin/config/trail-mapper` |
| `external_pg` | Service layer (`ExternalPgService`) for external PostgreSQL TrailMapper DB |
| `geo_content_builder` | Custom entities for plotting geographic content on Leaflet maps |
| `leaflet_full_page` | Generic full-page Leaflet mapping; configurable GeoJSON endpoint at `/leaflet-full-page/map-items/{bundle}` |
| `trailmapper_safeguards` | Prevents invalid `menu_name` on MenuLinkContent entities |

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
| Trail report | `/trails/bike/[title]`, `/trails/hike/[title]`, `/trails/ski/[title]` |
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
