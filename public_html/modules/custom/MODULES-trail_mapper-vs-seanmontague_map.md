# trail_mapper vs seanmontague_map — what each module is for

Two custom modules touch maps/geo on this site. They are **not** duplicates — they
own different layers. This doc explains the split so it's clear where new geo/map
code belongs.

---

## One-line distinction

- **`trail_mapper`** = the **GeoJSON / track / elevation engine**. Turns raw track
  data (external PostgreSQL, uploaded geoshape media) into GeoJSON, computes
  elevation profiles and per-track stats. Domain: *the data behind a track*.
- **`seanmontague_map`** = the **site-specific map aggregator**. Collects the
  site's geo entities (Places, POIs, Lodging, Destinations) and node references
  into markers / a unified GeoJSON endpoint for the map UI. Domain: *what shows up
  on a map and where*.

Rule of thumb: **track data, elevation, GeoJSON generation → trail_mapper.
Markers, map aggregation, what-pins-go-on-which-map → seanmontague_map.**

---

## trail_mapper

**Module description (info.yml):** "Generates GeoJSON from external PostgreSQL
data."

**Responsibility:** the low-level geo/track engine. It is the source of truth for
turning track data into renderable GeoJSON + the derived numbers (distance,
ascent/descent, elevation profile).

**Contains:**
- `Service/GeoShapeConverter.php` — converts geoshape source data → GeoJSON
  (registered service `trail_mapper.geo_shape_converter`).
- `Service/GeoElevationCalculator.php` — noise-filtered elevation gain/loss,
  resampling, grade/vertical thresholds.
- `GeoJsonGenerator.php` — GeoJSON assembly.
- `Controller/GeoJsonController.php` — endpoint serving generated GeoJSON.
- `Form/TrailMapperSettingsForm.php` — module settings.
- `Service/ArticleGeoData.php` — **(article refactor)** computes an article node's
  track/geo/stats DISPLAY data: geojson_urls, track_stats, dist_modes, the
  category-gated stats bar, is_gpx, map_center fallback. Lives here because it is
  track/geo/stats domain — trail_mapper's wheelhouse. Registered as
  `trail_mapper.article_geo_data`.

**Why it's its own module:** the GeoJSON/elevation engine is reusable, testable,
and independent of *which* site or *which* entities reference it. It would work on
any Drupal site with track data. It knows nothing about the homepage, articles, or
the site's specific content model — it just processes tracks.

---

## seanmontague_map

**Module description (info.yml):** "Site-specific map aggregating Places, POIs,
Trail Articles, and Trips into a single GeoJSON endpoint for seanmontague.com."

**Responsibility:** the **site-specific** aggregation layer. It knows *this site's*
content model — that articles reference POIs/destinations/lodging, that the
homepage has `field_map_markers`, that lodging pins are brown and the rest green —
and assembles the markers / map data the UI consumes.

**Depends on:** `node`, `serialization`, `geo_entity` (it works with the site's
geo_entity bundles: poi, lodging, destination).

**Contains:**
- `Controller/SeanMapController.php` — the aggregated GeoJSON endpoint (Places +
  POIs + Trail Articles + Trips → one feed).
- `Service/ArticleMapData.php` — **(article + front-page refactor)** builds map
  MARKERS + base map config from a node:
  - `build()` → article nodes (schema_destination / schema_poi / schema_lodging →
    typed markers).
  - `buildPage()` → page/front nodes (field_map_markers → color-coded markers,
    map_center from schema_geo, map_zoom from field_map_zoom).
  Registered as `seanmontague_map.article_map_data`. Lives here because markers are
  site-content aggregation — which of *this site's* entities become pins.

**Why it's its own module:** this is the glue that's specific to seanmontague.com's
content model and map UX. It is explicitly NOT reusable on another site (the
description says "for seanmontague.com"). It depends on the site's geo_entity
bundles and field names. Keeping it separate from trail_mapper keeps the reusable
engine clean of site-specific assumptions.

---

## Why both exist (the layering)

```
┌─────────────────────────────────────────────────────────────┐
│  seanmontague_map  (SITE-SPECIFIC aggregation)               │
│  "Which of THIS site's entities become markers, and how."    │
│  - ArticleMapData: node refs → markers (typed/colored)       │
│  - SeanMapController: Places+POIs+Articles+Trips → feed       │
│  knows: the content model, field names, marker colors        │
└───────────────────────────┬─────────────────────────────────┘
                            │ sits on top of
┌───────────────────────────┴─────────────────────────────────┐
│  trail_mapper  (REUSABLE engine)                             │
│  "Turn track data into GeoJSON + elevation + stats."         │
│  - GeoShapeConverter / GeoElevationCalculator / GeoJsonGen   │
│  - ArticleGeoData: track media → geojson_urls/stats/profile  │
│  knows: tracks, GeoJSON, elevation math — NOT the site       │
└─────────────────────────────────────────────────────────────┘
```

- **trail_mapper** is the engine — site-agnostic, could be reused elsewhere.
- **seanmontague_map** is the site harness — knows seanmontague.com's entities,
  fields, and map UX, and uses trail_mapper's output where it needs track data.

A clean test for "which module does new code go in?":
- Does it process tracks / compute GeoJSON / elevation / distance? → **trail_mapper**.
- Does it decide which of the site's entities/fields become markers or feed the
  map UI? → **seanmontague_map**.

---

## Where the article/page display services fit

The recent refactor moved template logic into services, split across both modules
along exactly this line:

| Service | Module | Why there |
|---|---|---|
| `ArticleGeoData` | trail_mapper | track/geo/stats data (geojson_urls, track_stats, dist_modes, stats bar) — engine domain |
| `ArticleMapData` | seanmontague_map | markers + map config (which referenced entities become pins) — site aggregation domain |

The theme's preprocess hook (`includes/node.theme`) calls BOTH — `ArticleGeoData`
for the track/stats data and `ArticleMapData` for the markers — then composes the
unified `has_map` and the template variables. So the two modules' outputs meet in
the theme layer, each having done its own job.

---

## Note on naming

`ArticleMapData` now also serves PAGE nodes (via `buildPage()`), so the "Article"
in the class name is slightly narrow — it's really "node map data." Renaming is
optional churn (the service id `seanmontague_map.article_map_data` is referenced in
the hook); flagged only so the name isn't read as "articles only." If renamed
later, do it as an isolated rename (class + service id + the hook's service call).
