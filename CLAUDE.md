# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# SeanMontague.com — Project Root

## Project Overview

Personal site for Sean Montague (seanmontague.com) — covering Kingdom Trails mountain biking, Burke Mountain skiing, permaculture/food forest, and Leaflet-based interactive mapping. Built on Drupal 10/11 with a custom theme (Surface) and custom geospatial modules. Sean is a web developer at the Smithsonian NMNH.

## Environment

- **Local:** Lando (Pantheon recipe) — `lando start` to start services
- **Hosting:** Pantheon
- **PHP:** 8.3, **Database:** PostgreSQL 15 with PostGIS
- **URL:** https://seanmontague.lndo.site

Always prefix PHP/Drupal/frontend commands with `lando` — never run `drush`, `composer`, or `npm` directly on the host.

## Common Commands

```bash
# Start local environment
lando start
lando info                    # show URLs and credentials

# Drupal
lando drush cr                # clear cache
lando drush cim               # import config
lando drush csex              # export config

# Composer
lando composer install
lando composer require drupal/module_name

# Frontend — cd into theme dir first, then run via lando ssh or:
# lando ssh -c "cd public_html/themes/custom/surface && npm run build"
lando npm run build           # full production build (lint + vite)
lando npm run watch           # dev mode: Vite + Storybook on localhost:6007
lando npm run lint:fix        # Biome JS/TS auto-fix
lando npm run stylelint:fix   # CSS auto-fix

# Tests
lando php vendor/bin/phpunit public_html/modules/custom  # PHPUnit for custom modules
php tests/Unit/SomeTest.php                              # standalone test scripts
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
| `trail_mapper` | Generates GeoJSON from external PostgreSQL TrailMapper DB |
| `external_pg` | Service layer (`ExternalPgService`) for external PostgreSQL TrailMapper DB; credentials are hardcoded in `ExternalPgService.php` |
| `geo_content_builder` | Custom entities for plotting geographic content on Leaflet maps |
| `leaflet_full_page` | Full-page Leaflet mapping with custom paragraph types |
| `trailmapper_safeguards` | Prevents invalid `menu_name` on MenuLinkContent entities |
| `global_volcanism` | Smithsonian NMNH GVP integration |
| `gvp_external_resources` | GVP external resources views/fields |
| `custom_entity` | Placeholder/scaffolding — incomplete (`@todo`) |

## Coding Standards

- **Drupal:** [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards)
- **CSS:** BEM (Block Element Modifier)
- **JavaScript:** ES6+
- **Theme:** Modified Atomic Design — Base → Elements → Components → Collections → Layouts → Pages
- **Twig:** Use namespaces (`@components`, `@elements`, etc.) for all includes; never `@components/surface/`

## Schema.org Content Types

Create types in this dependency order — dependencies must exist before dependents:

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

## Config Management

Always export after UI changes before committing:

```bash
lando drush csex
git add config/sync
git commit -m "Export config for [feature]"
```

Always import on environment setup or after pulling:

```bash
lando drush cim && lando drush cr
```

## Git Workflow

- Do not commit: `public_html/core`, `public_html/modules/contrib`, `public_html/themes/contrib`
- Config changes (`config/sync`) should be committed with the feature that requires them
- Do not edit `settings.php` directly — local overrides go in `settings.lando.php`

## Tests

Add unit tests to `public_html/modules/custom/*/tests/`. Run with `lando phpunit` or as standalone PHP scripts.

## Theme Reference

See `public_html/themes/custom/surface/CLAUDE.md` for full theme architecture, Twig rules, content model, and component patterns.
