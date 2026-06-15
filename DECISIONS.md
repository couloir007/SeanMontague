# Decisions — seanmontague.com

The settled calls and the reasoning behind them. `AGENT-GUARDRAILS.md` says
*what* the rule is; this file says *why*, so the decision doesn't get reopened
or "fixed" by someone who doesn't know the reason. Each entry: the decision, why,
and (where useful) what it rules out.

---

### Node templates are pure markup; data lives in preprocess hooks + services
Data logic moved out of Twig into `surface_preprocess_node__<suggestion>()` hooks
(in `includes/node.theme`) that delegate to services.
**Why:** inline Twig logic was inconsistent and untestable; preprocess is testable
PHP and services are reusable across pages. There is deliberately **no base
`surface_preprocess_node()`** — each suggestion hook auto-fires because its
template registers the theme hook.
**Rules out:** `{% set %}` data chains in node templates; a catch-all base hook.

### Two map services, split by responsibility
`trail_mapper` is the geo/track/distance/elevation **engine**;
`seanmontague_map` owns **markers + site-level map aggregation/config**.
**Why:** the heavy geospatial math is written once and reused by article, trip,
and the map page; marker/aggregation concerns are separate and change for
different reasons.
**Rules out:** per-page GeoJSON/track parsing (it forks and drifts).

### Track + distance source of truth = `schema_geoshape` media, not node fields
Per-track `field_distance` (METERS) + `field_route_type` live on the geoshape
media, processed once in the media presave hook. The old `schema_distance_*`
node fields were removed.
**Why:** media is created several ways (trip importer, standalone upload, file
replace) — one processing point keeps them identical. The node-level distance
fields drifted to 0 and duplicated the truth.
**Rules out:** recomputing or re-storing distance on the node.

### Metric storage, display-time conversion
All distances/elevations are stored and emitted in **METERS** with
`unit: 'distance'`; the units toggle converts at render
(localStorage → drupalSettings → `navigator` region → imperial for US/LR/MM).
**Why:** one canonical unit avoids drift; the reader's choice is applied without
re-storing anything.
**Rules out:** hardcoding `'mi'`; storing pre-converted values.

### Trip headline count is Destinations, not Points of Interest
Destinations = unique `tourist_destination` nodes across the itinerary, **deduped
by id**. POIs (`geo_entity:poi`) are a finer layer, shown as map markers.
**Why:** destinations are the trip's *stops* — the meaningful "how many places"
metric. They are a different entity type than POIs, not a synonym, so the two
counts measure different things.
**Rules out:** counting POIs as the headline trip size; conflating the two.

### All itinerary tracks render on the trip map, via the shared engine
The trip aggregates `geojson_urls` + `track_stats` across all itinerary articles
using the **same** `ArticleGeoData` track method the article uses.
**Why:** the trip map and an article map must not diverge — one track parser,
one styling path.
**Rules out:** a second, trip-specific track collector.

### Editorial variants are fields/facets, not new bundles or URL patterns
Navigation is handled by **menus**, not path patterns. Content variants (e.g.
log vs. guide trail content) are expressed with a field/facet on the existing
bundle, kept editorially distinct but with identical Schema.org output
(`@type: BlogPosting`).
**Why:** avoids bundle sprawl and duplicated field config; one index page with
filters is simpler than parallel content types.
**Rules out:** spinning up a bundle (or a URL scheme) to express a distinction a
field can carry.

### `dist/` is committed; Pantheon has no build step
The Vite build output under `dist/` is committed to the repo and served directly
in production.
**Why:** Pantheon runs no build; prod serves the committed assets.
**Rules out:** assuming CI will build; hand-editing `dist/` (it's regenerated).

### npm on the host; drush/composer/terminus via Lando
The Lando appserver is PHP-only (no node service), so `npm` runs on the host,
while PHP tooling runs inside Lando (`lando drush`, `lando composer`,
`lando terminus`, `lando php`, `lando phpunit`).
**Why:** container scope — node simply isn't in the appserver.

### Sean does all git; agents never commit
AI agents show diffs and stop; commits, branches, and pushes are Sean's.
**Why:** a human review gate, atomic commits, and deliberate inclusion of
`dist/` and config changes with the feature that needs them.

### Twig-first, then refactor to preprocess/service
New features are proven inline in Twig first, then the logic is extracted to a
preprocess hook or service once the shape is stable.
**Why:** iterate on the visible result; refactor when the data shape is known,
not before. (The article and trip refactors are the *second* step of this for
their features.)
