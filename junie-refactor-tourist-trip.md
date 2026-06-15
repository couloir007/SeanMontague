# Junie task — refactor `node--tourist-trip.html.twig`, extending the article map stack

Read `AGENT-GUARDRAILS.md`, `.junie/guidelines.md`, and
`README-node--article.md` first, then do the following.

## Goal
Refactor the TouristTrip display to the same pattern as `node--article`: the
`.twig` is pure markup; data is built in a preprocess hook that delegates to the
two map services. Crucially, the trip **reuses the article's track engine** —
it does not re-implement GeoJSON/distance handling.

Two behaviours the trip must gain:
1. **All tracks from all itinerary articles render on the trip map** (multi-track,
   same as an article map but aggregated across the trip).
2. **Per-mode distance totals** (driving / cycling / hiking / walking) summed
   across all days, sourced from track media — replacing the dead
   `schema_distance_*` fields.

## The reuse — extend `ArticleGeoData`, don't fork it
`ArticleGeoData::build()` already loops `schema_geoshape` media on one node and
produces `geojson_urls`, `track_stats` (index-matched, METERS), and `dist_modes`
(per-route_type distance sums, METERS). Do this:

1. **Extract** that per-node `schema_geoshape` media loop into a shared protected
   method, e.g. `collectTracks(NodeInterface $node, array &$geojson_urls, array
   &$track_stats, array &$dist_modes): void`. `build()` calls it once (behaviour
   unchanged — verify the article page still renders identically).

2. **Add** `buildTrip(NodeInterface $trip): array`. It walks
   `$trip->get('schema_itinerary')` -> article nodes and calls `collectTracks()`
   for **each**, accumulating into the SAME three arrays. Result:
   - `geojson_urls` — every day's track URLs, concatenated (all tracks)
   - `track_stats`  — every day's track stats, concatenated, index-matched to
     `geojson_urls` (so per-track route_type colour/dash/name styling works)
   - `dist_modes`   — distances summed by route_type key across the whole trip
   Then build a `distance_stats` array from `dist_modes` using the existing
   `MODE_LABELS` logic: single mode -> label `Distance`; multiple modes ->
   `Distance Driving` / `Distance Cycling` / etc. Each entry MUST be
   `{label, value: meters, unit: 'distance', meters: meters, is_small: <multi>}`
   — **meters + `unit: 'distance'`, never a hardcoded `'mi'`** (the units toggle
   converts at display, same as the article).
   Return at least: `geojson_urls`, `geojson_url` (first), `track_stats`,
   `dist_modes`, `distance_stats`, `has_geoshape` (= `!empty($geojson_urls)`).

## What else to build

3. **`surface_preprocess_node__tourist_trip()`** in `includes/node.theme`
   (new hook, mirror `surface_preprocess_node__article()`). It:
   - calls `trail_mapper.article_geo_data->buildTrip($node)` -> tracks + distances
   - calls `seanmontague_map.article_map_data->buildTrip($node)` -> markers + map config
   - builds the **stats bar**: `Days` (from `schema_trip_dates`), then the
     `distance_stats` entries, then `Destinations` (see below). POIs stay map
     markers; add a `Points of Interest` stat ONLY if Sean later asks.
   - resolves dates, hero image, body, narrative
   - assigns one `$variables['trip_data'] = [...]` feeding the trip collection
     contract, INCLUDING `geojson_urls` and `track_stats`.

4. **Destinations count** — collect `tourist_destination` nodes across all
   itinerary articles' `schema_destination`, **dedupe by node id**, and use the
   count as the `Destinations` stat. (The current "Sites"=POI-count stat is
   wrong for the headline; POIs are a finer layer.) The per-day `destinations`
   strip can stay article-based as it is — that's the day list, separate from the
   count.

5. **`@collections/trip/trip.twig`** — its map include already passes
   `geojson_urls`; ADD `'track_stats': track_stats|default([])` to that same
   `@components/map/map.twig` include so the aggregated tracks render with proper
   per-route styling and labels. (Twig-only change — Twig is read from `source/`
   via namespaces, so NO `npm run build` and NO `dist/` change.)

6. **`node--tourist-trip.html.twig`** -> pure markup: a doc-comment (variable ->
   source map, like the article) + `{% include '@collections/trip/trip.twig'
   with trip_data only %}`. **Delete** the broken inline track collection (the old
   `schema_geoshape.entity` grab got only the first media of the first day) and
   the dead `schema_distance_*` block — both are now handled by `buildTrip()`.

7. **Delete** the legacy `templates/content/node--trip.html.twig`.

## Data-model facts (current template has bugs — do NOT copy them)
- Trip title: `$node->label()` — NOT `node.label.value` (empty; `label` is a method).
- Body field is `body` (`content.body|field_value`) — NOT `field_body`.
- Trip has NO `schema_destination`; destinations/POIs come via `schema_itinerary`
  -> article nodes (`article.schema_destination` -> `tourist_destination` nodes;
  `article.schema_poi` -> `geo_entity:poi`).
- Geofield: `->lat` / `->lon` direct (NO `->value`). Entity label: `->label()`.
- `schema_trip_dates` is Smart Date: read item `[0]` (not `->first()`), `value` /
  `end_value` (Unix). Guard `end <= start` (zero-duration imports). Days =
  `round((end-start)/86400)+1`.
- Track media fields (already handled inside `ArticleGeoData`): `field_distance`
  (METERS), `field_route_type` -> `field_key` (`driving/cycling/hiking/walking/ferry`).

## Verify
- `lando drush cr`. On a tourist_trip: title + dates render; the map shows **every
  day's track** (not just day 1) with per-route colours, plus destination/POI
  markers; the stats bar shows Days, per-mode distances (Driving/Cycling/etc.,
  in the user's units), and a deduped Destinations count; body renders; no notices.
- Confirm an ARTICLE page still renders identically (the `collectTracks()`
  extraction must be behaviour-preserving for `build()`).
- `node--tourist-trip.html.twig` has no `{% set %}` data logic beyond the body.

Don't commit — show me the diff.
