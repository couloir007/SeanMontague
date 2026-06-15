# Node Template Refactor Playbook

The repeatable recipe for taking any `node--*.html.twig` from inline-Twig logic
to **pure markup + a preprocess hook + services**. This is the pattern proven on
`node--article` and `node--tourist-trip`; apply it to every remaining node type.

Reference this from agent prompts: open with *"Read `AGENT-GUARDRAILS.md` and
`PLAYBOOK-node-refactor.md`, then …"* so the prompt only has to carry the
page-specific facts.

---

## When to apply
Any node template that computes data inline — `{% set %}` chains, `{% for %}`
loops over field items, field-access logic, marker/stat assembly. If the template
does more than include components and resolve one render array, it's a candidate.

## The target shape — three layers

1. **Template = pure markup.** One `{% include %}` per component, in render
   order. The ONLY value resolved Twig-side is `body`
   (`content.body|field_value`), because it's a render array. No data logic.
2. **Preprocess hook** = `surface_preprocess_node__<suggestion>()` in
   `includes/node.theme`. Shapes all data, delegates heavy work to services, and
   assigns one structured array (e.g. `article_header`, `trip_data`) plus the
   flat vars the components consume.
3. **Services** = the heavy logic, reused across pages:
   - `trail_mapper.article_geo_data` — geo / track / distance / elevation engine
   - `seanmontague_map.article_map_data` — markers + base map config
   Extend an existing service method; never copy its logic into a new page.

## Why the hook fires (the mechanism)
A `node--*.html.twig` template registers its theme hook, and the matching
`surface_preprocess_node__<suggestion>()` is auto-attached and fires. **There is
no base `surface_preprocess_node()`** — don't add one. To wire a new page:
create the template, then the matching hook. Suggestions can be bundle
(`__tourist_trip`) or bundle + view mode (`__person__front`).

## The recipe (step by step)
1. **Name the suggestion** — bundle, or bundle + view mode.
2. **Inventory** the current template's inline logic — list every `{% set %}` and
   field access, and which component each value feeds.
3. **Move** each into the preprocess hook, using the verified accessors below.
4. **Delegate** any map / geo / track / distance work to the services. If the
   logic already exists for another page, **extract the shared part into a
   protected method and call it from both** — do not duplicate the loop.
5. **Reduce** the template to includes + the one `body` render.
6. **Write** `README-node--<type>.md` (see the convention below).
7. **Verify**: `lando drush cr`, render the page, no PHP notices; and confirm any
   code path you extracted still behaves identically for its original caller
   (a shared method must be behaviour-preserving).

## Compose the SHARED collections — never fork a per-bundle rendering stack
A refactored node template is **pure markup that composes the shared collections
directly** — the article composes `article-header` + `@collections/map-section` +
`@layouts/content-aside`. A new page type reuses those SAME collections, shaped by
its hook; it does NOT get its own monolith collection that re-implements header /
map / body. If two pages both show a map, they both go through `map-section` — one
map-rendering path, not two.

The component hierarchy enforces this: a **collection may include only components
and elements**, so another collection (`map-section`) or a layout
(`content-aside`) can ONLY be composed at the **node-template / page** level. That
is the structural reason the article composes at the template level instead of
wrapping everything in an `@collections/article` monolith — and the reason a
`@collections/<bundle>` monolith is an anti-pattern. Page-specific UI (a trip's
destinations strip, itinerary, cards) becomes its own component that **slots into**
the shared shell, not a parallel stack around it.

> Lesson learned (trip refactor): reusing the *engine* (`buildTrip`) but keeping a
> separate `@collections/trip` rendering stack is NOT "the same model." Same model
> = same shared collections, composed at the template level, differing only in
> hook-shaped data and in genuinely page-specific components.

## Verified accessors (the ones the codebase gets wrong)
- **Geofield:** `$e->get('schema_geo')->lat` / `->lon` — direct floats. `->value`
  is NULL. (Twig: `entity.schema_geo.lat`.)
- **Entity label:** `$e->label()` — a method. `->label->value` / `.label.value`
  is empty. (Node `title` IS a real field, so `node.title.value` is fine.)
- **Smart Date:** read item `[0]`, not `->first()` (sandbox-blocked). `value` /
  `end_value` are Unix timestamps; guard `end <= start` (zero-duration imports).
- **Body field name:** confirm it's `body` vs `field_body` on the actual bundle
  before rendering — they differ across content types.

## The map stack (any page that needs a map)
- `@collections/map-section` renders map + elevation. It is fed: `map_id`,
  `map_center`, `map_zoom`, `tiles`, `map_markers`, `geojson_url(s)`,
  `track_stats`.
- `@components/map` draws **tracks** from `geojson_urls`, styled per-track by
  `track_stats` (route_type → colour / dash / weight / name), plus `markers`.
- **Tracks** come from `ArticleGeoData` (`geojson_urls` + `track_stats` +
  `dist_modes`, all in METERS). **Markers** come from `ArticleMapData`.
- To put tracks on a **new** page: aggregate via the shared track method, and
  pass BOTH `geojson_urls` AND `track_stats` to the map (URLs alone draw flat,
  unstyled lines).
- **Distances:** store and emit METERS with `unit: 'distance'`; the units toggle
  converts at display. Never hardcode `'mi'`.

## README convention (one per refactored template)
Every refactored node template gets a `README-node--<type>.md` shaped like
`README-node--article.md` (the exemplar):
- rendering pipeline, outermost → innermost
- variable → source table ("to change X, edit the file in this column")
- what triggers conditional blocks (e.g. the `has_map` union)
- component composition order
- any KNOWN BUG deliberately deferred

This is what lets the next person (or agent) change one value without re-reading
the whole stack.

## What NOT to do
- Don't fork logic across pages — extend a shared service method.
- Don't recompute tracks/distances per page — reuse the engine.
- Don't preserve a bug the old template models; the correct version wins.
- Don't `{% extends %}` site-container or render nav in a node template — page
  chrome lives in `page.html.twig` + the header/footer regions.
- Don't leave data logic in the template "for now"; that's the thing being removed.

## The runway (node types still on inline-Twig)
Apply the playbook, roughly easiest → hardest:
`place` · `event` / `event_series` · `podcast_episode` / `podcast_series` ·
`person` (non-front) · `page` variants. Each gets a hook, possibly a service
method, and a README.
