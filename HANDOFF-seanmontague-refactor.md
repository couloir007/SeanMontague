# SeanMontague.com — Refactor Handoff

Status snapshot for continuing in a fresh chat. Covers the template logic-extraction
refactor, the About-as-Person content model, and the Schema.org JSON-LD fixes.

/ **Environment:** Drupal 10/11, Pantheon prod (dist/ committed, NO build step on
deploy), Lando local (https://seanmontague.lndo.site), PHP 8.3, PostgreSQL 15 +
PostGIS. Custom **Surface** theme (Storybook + Vite, Modified Atomic Design, flat
BEM, flat Twig namespaces). Schema.org Blueprints module. `schema_*` = Schema.org/
JSON-LD fields; `field_*` = internal. Sean does ALL git; agents never commit, never
touch dist/. Build runs on HOST npm (`npm run vite:build`), NOT `lando npm` (which
doesn't exist — appserver is PHP-only).

/ **Verified geofield accessor:** `$entity->get('schema_geo')->lat` / `->lon`
returns the float directly; `->lat->value` is NULL. Twig: `entity.schema_geo.lat`.

---

## DONE

### node--article.html.twig — fully refactored to pure markup
- All data from `surface_preprocess_node__article()` (in theme `includes/node.theme`)
  → two services. Template = doc comment + lone `{% set body = content.body|field_value %}`
  + includes/embed.
- **`trail_mapper.article_geo_data`** (`ArticleGeoData.php`): track/geo/stats —
  geojson_urls, track_stats, dist_modes, category-gated stats bar, is_gpx, map_center.
- **`seanmontague_map.article_map_data`** (`ArticleMapData.php`): markers + map config.
- Unified `has_map = has_geoshape OR has_geo OR has_place OR markers` (hook-computed).
- **3 latent bugs fixed:** marker-only articles now get a map; destination marker
  labels blank→named; article header title shows (was empty via `.label.value` bug).
- **1 bug deferred:** `category_name` still empty (`.label.value`); fix = `$category->label()`
  in ArticleGeoData.

### node--page--front.html.twig — refactored (mechanical parts)
- Double-shell fixed (removed `{% extends site-container %}` + `{% block main %}`;
  page.html.twig owns the one shell).
- Topics wired dynamically from `schema_topic` (icon ← field_icon, url ← field_url,
  text ← term.label); hardcoded list deleted.
- Map/marker/category logic → `surface_preprocess_node__page__front()` →
  `ArticleMapData->buildPage()`. Template's remaining `{% set %}`s = CTA links + includes.

### ArticleMapData — generalized (serves both)
- `build()` (article: 3 fields schema_destination/poi/lodging → type+label markers,
  zoom 13) and `buildPage()` (page: field_map_markers → color markers
  #a05a00 lodging / #3a5a40 else + label, center from schema_geo, zoom from
  field_map_zoom default 11) share `collectMarkers()` core via a decorator callback.
- Article path verified BYTE-IDENTICAL after generalization (node 99: 10 markers).

### About content → Person entity (content model)
- **Person bundle** holds the bio content: `body` → 3 paragraphs (maps to
  `description`); **`schema_knows_about`** (created, multi-value, mapped to
  `knowsAbout`) → the 7 skills; `schema_works_for` → NMNH.
- **Page node fields** (created): `field_about_label` (string "About"),
  `field_about_title` (text/formatted, allows `<em>` — "Builder, rider, <em>grower.</em>").
- Homepage references the Person via **`schema_main_entity`** (it's a Layout-
  Paragraphs `entity_reference_revisions` field by default — but the paragraph use
  is unused; it now references the Person for mainEntity). Person also has a **`front`
  view display mode** (renders only body + schema_knows_about) for the About section.

### Schema.org JSON-LD — homepage fully fixed
Module: `seanmontague_schemadotorg` (custom JsonLd classes + 2 alter hooks).
- **`PersonJsonLd.php`** (new): adds `knowsAbout` (from schema_knows_about) +
  `description` (from body, plain-text w/ paragraph spacing) to the Person.
- **`.module` edits:** `use PersonJsonLd`; person branch in
  `..._schema_type_entity_alter()` (matches `in_array('Person', (array)$data['@type'])`
  — @type is `['Person','ProfilePage']`); BUT that per-entity hook does NOT fire for
  the mainEntity *reference* stub — so enrichment actually happens in the **page-level
  hook** (`..._jsonld_alter`): loads the Person from `schema_main_entity` and applies
  `PersonJsonLd::alter()` to `$data['schemadotorg_jsonld_entity']['mainEntity']`.
- **`_seanmontague_normalize_url_keys()`**: recursive `@url`→`url` (Blueprints emits
  invalid `@url`; `@id` left alone). Called LAST in page-level hook.
- **`_seanmontague_normalize_iso_dates()`**: date keys → strict ISO 8601 (`date('c')`).
  Called after the url normalizer.
- **VERIFIED homepage JSON-LD:** `url` everywhere (no `@url`), ISO dates, mainEntity =
  Person with all 7 knowsAbout + full description (proper sentence spacing).

---

## NEXT (nothing urgent)

### About section — visual render still pending
The Person `front` display mode renders default field markup, NOT the styled
`@components/about/about.twig` component. To get the designed About section:
- Add **`node--person--front.html.twig`** that includes `@components/about/about.twig`,
  mapping Person's `body` → paragraphs and `schema_knows_about` → skills.
- `label`/`title` come from the PAGE (`field_about_label`/`field_about_title`), not
  the Person — so the homepage supplies them around the rendered Person, or via a
  small preprocess. (Person can't supply them; it doesn't know the page context.)
- Decide body→paragraphs handling: pass rendered body as a block (lean this way) vs.
  reconstruct the paragraph array.

### Landing-page mainEntity (the CollectionPage pattern — designed, not built)
`page` content type will be reused for landing pages (trips list, trail-reports list).
- Homepage `mainEntity` = Person (DONE).
- **Trips landing** = `CollectionPage`, `mainEntity` = `ItemList` of `TouristTrip`s.
- **Trail-reports landing** = `CollectionPage`, `mainEntity` = `ItemList` of
  `BlogPosting`s (trail_report bundle).
- ItemLists should be GENERATED from the listing view (not hand-set fields), via
  `hook_schemadotorg_jsonld_schema_type_entity_alter()` — likely a CollectionPageJsonLd
  / ItemListJsonLd class.

### Small cleanups
- Article hook "Undefined array key" warnings: map_text / map_lines /
  drupal_geo_rendered (static-null passthroughs — assign them or stop expecting them).
- Rename `_surface_article_svc_data` helper (drop the "svc" scaffolding label).
- `dist_modes` set by hook but unused by template (service still needs it internally
  for stats — just stop assigning to $variables).
- `category_name` `.label.value` bug — deliberate deferred fix (`$category->label()`).
- Front-page CTA links (cta_primary/cta_ghost) could move to the hook for a fully
  data-free template (optional).

### Hardening
- Unit tests for ArticleGeoData + ArticleMapData (pattern: existing
  GeoElevationCalculatorTest).
- Validate homepage JSON-LD at validator.schema.org / Google Rich Results Test.
- Build-doc reconciliation: CLAUDE.md says `lando npm` (fiction — host npm only).
- Phantom-library audit: layout--section-two-col declares non-existent surface/layout.

---

## KEY FILES
- Theme hook: `public_html/themes/custom/surface/includes/node.theme`
- `public_html/modules/custom/trail_mapper/src/Service/ArticleGeoData.php`
- `public_html/modules/custom/seanmontague_map/src/Service/ArticleMapData.php`
- `public_html/modules/custom/seanmontague_schemadotorg/seanmontague_schemadotorg.module`
- `public_html/modules/custom/seanmontague_schemadotorg/src/JsonLd/` (Article, Lodging,
  PointOfInterest, TouristTrip, **Person**)
- Templates: `templates/content/node--article.html.twig`,
  `templates/content/node--page--front.html.twig`
- Component: `@components/about/about.twig` (expects label, title, skills[], paragraphs[])
