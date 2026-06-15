# node--article.html.twig — Architecture & Variable Map

How an article page is rendered, and **where to change each thing**. The template
itself is pure markup; all data is computed upstream in a preprocess hook that
delegates to two services. This README is the map from "the thing on the page" to
"the file that produces it."

---

## The rendering pipeline (outermost → innermost)

```
html.html.twig                         the <html><head><body> document (Drupal core)
└─ page.html.twig                      the PAGE SHELL — extends @layouts/site-container
   ├─ {{ page.header }}  (region)      site-header → nav  (site chrome; NOT this node)
   ├─ {{ page.content }} ──┐
   │                       └─ node--article.html.twig   ← THIS FILE (content only)
   └─ {{ page.footer }}  (region)      site-footer
```

`node--article.html.twig` renders **content only**. It does **not** extend
site-container and does **not** render the site nav — those are page chrome owned
by `page.html.twig` and the header region. (See "Why no extends / no nav" below.)

---

## Where the data comes from

The template references variables (`stats`, `map_markers`, `map_id`, `has_map`,
`article_header`, …) but **computes almost none of them**. They are built in:

```
includes/node.theme
  surface_preprocess_node__article(&$variables)     ← the preprocess hook
    ├─ trail_mapper.article_geo_data ->build($node)  ← track / geo / stats
    │     src: modules/custom/trail_mapper/src/Service/ArticleGeoData.php
    ├─ seanmontague_map.article_map_data ->build($node) ← markers / map config
    │     src: modules/custom/seanmontague_map/src/Service/ArticleMapData.php
    └─ (hook) computes the UNIFIED has_map + the article_header object
```

**The only value set in the template** is `body` — because it is a render array
(`content.body|field_value`), which is cleaner to leave Twig-side.

---

## Variable → source table

To change how a value is computed, edit the file in the "Source" column.

| Variable                | Source (where it's built)                              | Notes |
|-------------------------|--------------------------------------------------------|-------|
| `body`                  | **the template** (`{% set body = content.body|field_value %}`) | render array; the one Twig-side value |
| `article_header`        | hook (`includes/node.theme`)                           | object: title/subtitle/date/category/category_key/difficulty/stats/map_id |
| `stats`                 | `ArticleGeoData::build` → hook                         | category-gated per-mode distance bar + travel POI count (METERS) |
| `track_stats`           | `ArticleGeoData::build`                                | per-track distance/ascent/descent/min/max/duration (METERS) |
| `dist_modes`            | `ArticleGeoData::build`                                | per-route_type distance sums; feeds `stats` (not used directly in template) |
| `geojson_urls` / `geojson_url` | `ArticleGeoData::build`                         | track file URLs from schema_geoshape media |
| `is_gpx` / `geo_file_url` | `ArticleGeoData::build`                              | first track file; download is rendered via the schema_geoshape FIELD TEMPLATE, not these |
| `map_center`            | `ArticleGeoData::build`                                | fallback only; fitBounds overrides when geometry present |
| `has_geoshape` / `has_geo` / `has_place` | `ArticleGeoData::build`               | the three geo-source flags |
| `category_name` / `cat_key` | `ArticleGeoData::build`                            | category label + field_key (see KNOWN BUG below) |
| `map_markers`           | `ArticleMapData::build` → hook                         | destination + POI + lodging Point markers |
| `map_zoom` / `tiles`    | `ArticleMapData::build`                                | base map config |
| `has_map`               | **hook** (unified)                                     | = has_geoshape OR has_geo OR has_place OR (markers not empty) |
| `map_id`                | **hook**                                               | `has_map ? 'article-map-' ~ nid : ''` |
| `main_modifier`         | **hook**                                               | `has_map ? 'content-aside__main--with-map' : ''` |
| `map_text` / `map_lines` / `drupal_geo_rendered` | hook (static nulls)         | reserved / unused placeholders passed to map-section |

> `map_label` and `map_title` were **removed** — the article map-section renders
> neither (that's the homepage promo block's job). Do not re-add them here unless
> the article map-section is changed to render a title.

---

## Map data sources (what triggers a map)

An article gets a map when **`has_map`** is true. `has_map` is the union of:

- **`schema_geoshape`** (data_download media) → GeoJSON track(s) + elevation
  profile. Per-track stats live on the media in METERS.
- **`schema_geo`** (geofield on the node) → map-center fallback, no marker.
- **`schema_place`** (entity ref) → map-center fallback, no marker.
- **markers present** — `schema_destination`, `schema_poi`, or `schema_lodging`
  references with geo. **A marker-only article (no track/geo/place) still gets a
  map** — this is intentional; the map fitBounds to the markers.

Marker types/colors (built in `ArticleMapData`):
- `schema_destination` → type `destination` (sky blue)
- `schema_poi`         → type `poi` (forest green)
- `schema_lodging`     → type `destination` (sky blue)

Geofield coordinates use the **direct getter**: `$entity->get('schema_geo')->lat`
/ `->lon` (NO `->value`). Verified: `->lat` returns the float; `->lat->value` is
NULL on this site. Same as Twig's `entity.schema_geo.lat`.

---

## The component composition (what the template actually renders)

In order:

1. `@elements/reading-progress` — scroll progress bar.
2. `@collections/article-header` — title/date/category/difficulty + stats bar
   (rendered from the `article_header` object).
3. `@collections/map-section` — full-width map + elevation profile (the article
   route block; **not** the homepage promo, which is `homepage-map-section`).
4. `@layouts/content-aside` — the generic two-column shell (body + sidebar):
   - **main** slot → `<div class="prose">{{ body }}</div>`
   - **aside** slot → `{{ content.schema_geoshape }}` (GPX download via field
     template) + `{{ content.schema_poi }}` (POI side-card via field template)

### Field templates that render sidebar content

These produce the sidebar cards via Drupal's field render pipeline (NOT built in
the node template):

- `templates/field/field--node--schema-poi--article.html.twig` → POI side-card
  (reads `field_poi_type` for the type label; uses `@components/side-card` poi variant)
- the GPX download renders through `{{ content.schema_geoshape }}` (its field
  template / display) — the node no longer builds a download card.

---

## Why no `{% extends %}` and no nav

`page.html.twig` already extends `@layouts/site-container` (the ONE page shell).
If this node template also extended site-container, the shell would nest inside
itself (two `.site-container` wrappers, an empty inner header). So this template
extends nothing and renders content only. The site nav comes from the **header
region** (`site-header` → `nav`), not from any node template.

---

## KNOWN BUG (deliberately deferred)

`category_name` is **empty**. The original Twig used `category.label.value`, which
is buggy: `.label` is the entity's label *method* (a string), and `.value` on a
string yields nothing. `ArticleGeoData` ports this faithfully (returns `''`), so
`category_name` is empty and the article-header's category renders blank — as it
always has.

**The fix** (when wanted): in `ArticleGeoData::build`, set
`$category_name = $category->label();` (the method) instead of the buggy
field-value read. The same `.label.value` bug previously affected marker labels,
the map title, and the header title — those were fixed; `category_name` is the
last one left, kept bug-for-bug because it only falls through to a (currently
unused) `'Map'` fallback and a blank header category.

---

## Quick "I want to change X" index

- **Add/adjust a stat in the top bar** → `ArticleGeoData::build` (the `stats` /
  `MODE_LABELS` logic).
- **Change marker color/type or add a marker source** → `ArticleMapData::build`.
- **Change what makes an article show a map** → the `has_map` union in the hook
  (`includes/node.theme`).
- **Change the header fields (title/date/difficulty)** → the `article_header`
  array in the hook.
- **Change the body/sidebar layout** → `@layouts/content-aside` (twig + css).
- **Change the POI card** → `field--node--schema-poi--article.html.twig` +
  `@components/side-card`.
- **Change the map/elevation block** → `@collections/map-section`.
- **Fix the empty category** → see KNOWN BUG above.
- **Change page chrome (nav/header/footer)** → NOT here; see `page.html.twig` +
  the header/footer regions + `@layouts/site-header` / `site-footer`.
