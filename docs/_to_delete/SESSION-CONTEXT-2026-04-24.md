# Session Context — seanmontague.com
# April 24, 2026 — Continuation of ongoing development

This document captures all decisions, bugs fixed, files produced, and
pending work from the April 24 session. Read alongside CLAUDE.md,
TEMPLATES.md, TOURIST-DESTINATION.md, PERSISTENT-AUDIO-PLAYER.md, and SEAN.md.

---

## Files produced this session

| File | Location | Status |
|---|---|---|
| `trip.twig` | `collections/trip/` | ✅ Complete |
| `trip.css` | `collections/trip/` | ✅ Complete |
| `trip.js` | `collections/trip/` | ✅ Complete |
| `trip.yml` | `collections/trip/` | ✅ Complete — Ireland 2024 fixture |
| `trip.stories.jsx` | `collections/trip/` | ✅ Complete |
| `node--tourist-trip.html.twig` | `templates/content/` | ✅ Complete — see bugs fixed below |
| `TEMPLATES.md` | `templates/` | ✅ Complete — full inventory |
| `TOURIST-DESTINATION.md` | project root | ✅ Complete |
| `PERSISTENT-AUDIO-PLAYER.md` | project root | ✅ Complete |
| `SEAN.md` | project root | ✅ Complete |
| `claude-code-destination-content-type.md` | project root | ✅ Ready to run |
| `TripImportBatch.php` | `trip_import/src/Batch/` | ✅ Fixed — 3 bugs |
| `FacetGuardMiddleware.php` | `facet_guard/src/StackMiddleware/` | ✅ Fixed — CIDR whitelist |
| `FacetGuardSubscriber.php` | `facet_guard/src/EventSubscriber/` | ✅ Fixed — CIDR whitelist |
| `FacetGuardSettingsForm.php` | `facet_guard/src/Form/` | ✅ Fixed — form description |

---

## Trip collection (`@collections/trip/trip.twig`)

### What it renders
1. Hero image
2. Title block — label, display title (Bebas Neue), dates
3. Destination strip — scrollable mono uppercase names with `·` separators
4. Stats bar — reuses `@components/stats-bar/stats-bar.twig`
5. Route map — reuses `@components/map/map.twig`, interactive
6. Narrative + sidebar — body prose with drop cap, blockquote, hr, h3 styles; sticky itinerary list
7. Destination cards grid — 4-col responsive, mini Leaflet maps via `trip.js`

### Variables
```twig
label, title, dates, image_url       — header
stats[]                              — {label, value, unit, is_small}
map_id, map_center, map_zoom         — route map config
map_label, map_note, map_markers[]   — route map content
tiles                                — tile set key
narrative_kicker, narrative_heading  — prose header
body                                 — rendered HTML prose
destinations[]                       — sidebar itinerary (articles, not geo_entities)
dest_nodes[]                         — destination cards (tourist_destination nodes)
```

### Sidebar — "Itinerary"
Populated from **articles** in `schema_itinerary`, not from geo_entity destinations.
One entry per article: `article.label` (title), `art_date`.

### Destination cards
Populated from `dest_nodes[]` — unique `tourist_destination` nodes collected
across all itinerary articles. Each card: mini-map, name, region, body
summary, link to `dest.url`. Built in `node--tourist-trip.html.twig`.

### surface.libraries.yml entry needed
```yaml
trip:
  css:
    component:
      dist/css/trip.css: {}
  js:
    dist/js/trip.js: {}
  dependencies:
    - surface/surface-map
    - surface/stats-bar
```

---

## node--tourist-trip.html.twig — key patterns

### Smart Date — always `[0]` not `.first`
```twig
{% set item     = node.schema_trip_dates[0] %}
{% set arrived  = item.value %}
{% set departed = item.end_value %}
{% set days     = ((departed - arrived) / 86400)|round + 1 %}
```

### Itinerary loop builds four things
```twig
{% for item in node.schema_itinerary %}
  {% set article = item.entity %}

  {# 1. Sidebar entry — one per article #}
  {% set destinations = destinations|merge([{
    'name':  article.label,   {# NOT article.title.value — causes FieldItemList error #}
    'dates': art_date,
  }]) %}

  {# 2. Distance totals #}
  {# total_driving, total_hiking, total_walking, total_cycling #}

  {# 3. Destination nodes — unique, deduped by dest.id #}
  {% for dest_item in article.schema_destination %}
    {% set dest = dest_item.entity %}  {# node:tourist_destination #}
    {# dest.schema_geo.lat / .lon — direct floats, NO .value #}
    {# dest.label — string, NO .value #}
    {# dest.url — available because destination is a node #}
  {% endfor %}

  {# 4. POI markers + count #}
  {% for poi_item in article.schema_poi %}
    {# poi.schema_geo.lat / .lon — direct floats, NO .value #}
  {% endfor %}
{% endfor %}
```

### Stats bar — activity data only
- Days (from Smart Date diff)
- Distance Driving / Hiking / Walking / Cycling (summed across articles)
- Sites (POI count)
- NO destinations count — that was wrong, removed

### Critical: entity label access
```twig
article.label      {# CORRECT — string property #}
article.title.value  {# WRONG — causes "Object could not be converted to string" error #}
dest.label         {# CORRECT — node label, string #}
```

---

## tourist_destination content type

### Status: UI complete, config exported, ready for Claude Code prompt

Machine name: `tourist_destination`
Schema.org: `TouristDestination` (subtype of Place)
URL pattern: `/destinations/[node:title]`

### Fields
| Machine name | Type | Schema.org |
|---|---|---|
| `title` | string | `name` |
| `body` | text_with_summary | `description` |
| `schema_image` | entity_ref → media:ImageObject | `image` |
| `schema_geo` | Geofield | `geo` |
| `schema_address` | Address | `address` |
| `schema_contained_in_place` | entity_ref → node:place | `containedInPlace` |
| `schema_tourist_type` | text plain, unlimited | `touristType` |

`field_day` — **check if this was added in UI**. Required for KMZ importer
to attach destination nodes to day articles via `createDayArticle`.

### Schema.org decisions
- `schema_destination` on article maps to `contentLocation` (confirmed in Blueprints)
- `schema_tourist_type` plain text — no taxonomy, type directly ("Traditional Music")
- `schema_activity_type` — **skipped entirely**
- `schema_contained_in_place` → `containedInPlace` — Blueprints may not auto-map, handle in hook
- Galway is a destination. Ireland is a Place. `containedInPlace` links them.

### Replaces
`geo_entity:destination` — **retired**. Do not use. Do not reference in new code.

### Planning map — POI visit status
Add `field_status` to `geo_entity:poi`:
- `did` — visited, solid `--forest` ring marker
- `wished` — wanted to, dashed `--muted` ring
- `next_time` — discovered after / future trip, `--sky` ring

Map shows the planning process — not just what happened but what was
considered. Useful to readers with different agendas or time constraints.

---

## Claude Code prompt ready to run

File: `claude-code-destination-content-type.md`

Six steps:
1. Verify config — `schema_destination` on article targets `node:tourist_destination`
2. Create `node--tourist-destination.html.twig` — hub page
3. Update `node--tourist-trip.html.twig` — dest traversal + `dest_nodes[]`
4. Update `trip.twig` cards section + `trip.css` — destination node cards
5. Update `TEMPLATES.md`
6. Add JSON-LD hook to `seanmontague_schemadotorg` module

**Before running:** confirm `field_day` exists on `tourist_destination` content type.

---

## TripImportBatch.php — bugs fixed

### Bug 1 — lodging copy-paste (critical)
Lodging was being populated with destination IDs. Root cause:
```php
// WRONG (was)
$node->set('schema_lodging', array_map(fn($id) => ..., array_values($dest_ids)));

// FIXED
$node->set('schema_lodging', array_map(fn($id) => ..., array_values($lodge_ids)));
```

### Bug 2 — importDestination creates geo_entity (wrong entity type)
Was creating `geo_entity:destination`. Now creates `node:tourist_destination`.
Duplicate detection changed from coordinate proximity to title match.
`field_day` stored on node for `createDayArticle` attachment.

### Bug 3 — createDayArticle queries geo_entity for destinations
Was: `geo_entity` query with `bundle = destination`
Fixed: `node` query with `type = tourist_destination` and `field_day = $day`

---

## facet_guard — whitelist fix

`FacetGuardMiddleware` had no whitelist check — bots were blocked before
the subscriber's whitelist was ever reached.

Fixes:
- Added `isWhitelisted()` CIDR-aware helper to both Middleware and Subscriber
- Middleware now checks whitelist as first action after resolving client IP
- Subscriber `in_array` replaced with `isWhitelisted()` — supports CIDR ranges
- Settings form description updated

To whitelist all Googlebot: add `66.249.64.0/19` to the IP Whitelist field
at `/admin/config/facet-guard/settings`.

---

## map.js fixes this session

### Hero background center/zoom override
`fitToData()` was called even for non-interactive maps. Fix:
```js
const fitToData = () => {
  if (!interactive) return;  // ← added
  // ...
};
```

### Hero tile set
Pass `tiles` from `hero.yml` through `hero.twig` to `map.twig`:
```yaml
# hero.yml
tiles: 'esri-nat-geo'
```
```twig
{# hero.twig #}
{% include '@components/map/map.twig' with {
  ...
  'tiles': tiles|default('usgs-topo'),
} only %}
```

---

## Persistent audio player — planned

See `PERSISTENT-AUDIO-PLAYER.md` for full spec.

Architecture:
- `drupal/turbo` — intercepts navigation, prevents DOM destruction
- `data-turbo-permanent` on player wrapper in `page.html.twig`
- `@components/player/player.twig` + `player.js` — listens for `surface-play` event
- `/api/audio/{node}` JSON endpoint — `surface_player` module
- `surface-play` CustomEvent dispatched by play buttons in node templates

**Check before building:** Turbo + Leaflet map interaction. The
`el._surfaceMapInit = true` guard in `map.js` persists across Turbo
navigations — may cause maps to not re-init when navigating back to a page.

---

## Homepage decision — pending

This was the next topic when the chat hit the file limit.

Options to discuss:
1. Basic Page node + Layout Paragraphs — editorial control, reuses LP setup
2. `page--front.html.twig` hardcoded + Views blocks — full theme control
3. Hybrid — hardcoded shell template, Views for content regions

Site sections to feature on homepage:
- Kingdom Trails MTB
- Burke Mountain skiing / telemark
- Permaculture / food forest
- Travel (Ireland 2024, etc.)
- Interactive map — scatter plot of places, POIs, trips

---

## Geofield access — confirmed rules (add to TEMPLATES.md)

| Context | Access | Notes |
|---|---|---|
| Node's own geofield | `node.schema_geo.lat.value` | `.value` needed |
| Referenced geo_entity geofield | `entity.schema_geo.lat` | Direct float, NO `.value` |
| Referenced node geofield | `dest.schema_geo.lat` | Direct float, NO `.value` |

This inconsistency is a known Drupal Twig quirk. The article template
uses `.value` on node geo and no `.value` on entity geo — follow that
pattern consistently.

---

## Ireland 2024 content — pending manual work

- [ ] Create Ireland Place node (`/places/ireland`)
- [ ] Create 8 tourist_destination nodes — Dublin, Sligo, Galway, Inishmore,
      Limerick, Killarney, Dingle, Kilkenny
      - Each: schema_geo, schema_address, schema_contained_in_place → Ireland
      - Galway lede: "Galway is the New Orleans of Irish music, and we had two days."
- [ ] Re-run KMZ import (after TripImportBatch.php fix deployed)
- [ ] Assign destination nodes to day articles via `schema_destination`
- [ ] Fix Day 6-7 POI labels in Google My Maps (need "Day N:" prefix)
- [ ] Delete `node--trip.html.twig` — legacy, has crash bugs, conflicts with
      `node--tourist-trip.html.twig` template suggestion

---

## node--trip.html.twig — DELETE THIS FILE

Has two crash bugs:
1. `node.schema_trip_dates.first` — SecurityError (use `[0]`)
2. `node.schema_destination` — field removed from tourist_trip bundle

As long as this file exists on disk, Drupal may use it instead of
`node--tourist-trip.html.twig`. Delete it.

---

## Design tokens (quick reference)

```css
--bg:       #f7f6f2
--surface:  #ffffff
--surface2: #f0ede6
--border:   #e0dbd1
--muted:    #7a7568
--text:     #2c2a25
--bright:   #161410
--forest:   #3a5a40   /* primary green — trails, links */
--trail:    #7a3410   /* brown — MTB, skiing */
--sky:      #4a7c9e   /* blue — maps */

--font-display-nek: 'Bebas Neue'
--font-serif-nek:   'Cormorant Garamond'
--font-mono-nek:    'DM Mono'
```

---

## Marker types (map.js surfaceMarker)

| Type | Shape | Ring color | Use |
|---|---|---|---|
| `poi` | triangle | `--forest` | Points of interest |
| `destination` | circle | `--sky` | Tourist destinations |
| `lodging` | square | `--amber` (#a05a00) | Hotels, B&Bs |
| `trail` | diamond | `--trail` | Trail heads |
| `place` | muted circle | `--forest` | Place hub nodes |

Planned additions:
| `wished` | ring only, dashed | `--muted` | POIs we didn't get to |
| `next_time` | ring only | `--sky` | Future visit POIs |

---

## Always prefix commands with `lando`

```bash
lando drush cr
lando drush cex / cim
lando drush php-eval "\Drupal::service('twig')->invalidate();"
lando composer require drupal/module
lando npm run build
lando npm run watch
```

JS files must include `/* jshint esversion: 6 */` (or 11 for newer files).
CSS uses BEM. Twig namespaces are flat — never `@components/surface/`.
DB is PostgreSQL 15 with PostGIS. PHP 8.3.
`map_id` event key is snake_case in `surface-map-ready` dispatches.
