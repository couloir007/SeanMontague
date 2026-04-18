# Claude Code Steps — Article Display Build

Reference files (all at project root):
- `CLAUDE.md` — theme architecture, Twig rules, JS standards
- `article-display-analysis.md` — full architecture spec
- `TrailMapperSettingsForm.php` — settings form class for Step 6

---

## Step 1 — trail_mapper module ✅ COMPLETE

- GPX (trk/trkseg/trkpt + rte/rtept formats) and GeoJSON conversion
- `hook_entity_presave` → `GeoShapeConverter` service
- Z values converted meters → feet
- `schema_geoshape` always contains `.geojson` after save
- `trail_mapper.services.yml` registered

---

## Step 2 — article-map-section.twig ✅ COMPLETE

Dual context: `drupal_geo_rendered` (Drupal) or `map.twig` (Storybook).
Passes `geojson_url`, `tiles`, `elev_data` to sub-components.

---

## Step 3 — elevation-profile.twig ✅ COMPLETE

`data-geojson-url` and `data-elev` attributes both present.

---

## Step 4 — elevation-profile.js ✅ COMPLETE

Priority: `_surfaceTracks` cache → `surface-map-ready` event →
`data-geojson-url` fetch → `data-elev` inline (Storybook).

---

## Step 5 — map.js ✅ COMPLETE

- ES6+, `/* jshint esversion: 6 */`, `"use strict"`
- Tile resolution: `drupalSettings.trailMapper` → `data-map-tiles` → `usgs-topo`
- GeoJSON fetch before Leaflet init — fitBounds on track + markers
- Filters Point features out before `L.geoJSON` rendering
- `flattenCoords()` handles LineString, MultiLineString, 4-value coords
- `computeTrackStats()` — Haversine distance + elevation gain/loss/min/max
- Stats dispatched with `surface-map-ready` event
- Ctrl+scroll zoom hint, touch two-finger hint
- Marker null/NaN guard
- Double-init guard on `initLeaflet`
- `window._surfaceMaps`, `window._surfaceTracks` registered

---

## Step 6 — article.twig ✅ COMPLETE

Passes `geojson_url`, `drupal_geo_rendered`, `tiles` to article-map-section.

---

## Step 7 — node--article.html.twig ✅ COMPLETE

- `has_geo` / `has_place` check `.value.lat is not empty`
- `schema_geo` = map center only, never a marker
- `schema_place` = markers with `entity_type`/`entity_id`
- `has_stats` only true when no geoshape and manual distance is set
- Stats computed from GeoJSON by `map.js` when geoshape present
- `drupal_geo_rendered: null` always

---

## Step 8 — trail_mapper admin form (PENDING)

### 8a — Manual: create config files

Create in `public_html/modules/custom/trail_mapper/`:

**`config/schema/trail_mapper.schema.yml`**
```yaml
trail_mapper.settings:
  type: config_object
  label: 'Trail Mapper settings'
  mapping:
    tile_key:
      type: string
      label: 'Tile set'
    tile_url:
      type: string
      label: 'Custom tile URL'
    tile_attribution:
      type: string
      label: 'Custom tile attribution'
```

**`config/install/trail_mapper.settings.yml`**
```yaml
tile_key: usgs-topo
tile_url: ''
tile_attribution: ''
```

**`trail_mapper.links.menu.yml`**
```yaml
trail_mapper.settings:
  title: 'Trail Mapper'
  description: 'Configure tile layers and map defaults.'
  parent: system.admin_config_development
  route_name: trail_mapper.settings
  weight: 10
```

### 8b — Claude Code

```
Read CLAUDE.md.

Add the following to the trail_mapper module. Show each file before writing.

1. Add to trail_mapper.routing.yml (create if missing):

trail_mapper.settings:
  path: '/admin/config/trail-mapper'
  defaults:
    _form: '\Drupal\trail_mapper\Form\TrailMapperSettingsForm'
    _title: 'Trail Mapper Settings'
  requirements:
    _permission: 'administer site configuration'

2. Create src/Form/TrailMapperSettingsForm.php from
   TrailMapperSettingsForm.php at the project root.
   Place in public_html/modules/custom/trail_mapper/src/Form/

3. Add to trail_mapper.module after existing use statements:
   use Drupal\trail_mapper\Form\TrailMapperSettingsForm;

   Add this function — do not replace existing code:

function trail_mapper_page_attachments(array &$attachments): void {
  $tile = TrailMapperSettingsForm::resolvedTile();
  $attachments['#attached']['drupalSettings']['trailMapper'] = [
    'tileKey'         => $tile['key'],
    'tileUrl'         => $tile['url'],
    'tileAttribution' => $tile['attribution'],
    'tileMaxZoom'     => $tile['maxZoom'] ?? 16,
  ];
}

Show diffs before writing.
```

### 8c — Verify

```bash
lando drush cr
```

Visit `/admin/config/trail-mapper` — should show tile set selector.

---

## Step 9 — Stats bar from GeoJSON (PENDING)

When `schema_geoshape` is set, stats (distance, gain, loss, min, max)
come from `computeTrackStats()` dispatched with `surface-map-ready`.
The stats bar needs to be populated by JS, not Twig.

```
Read CLAUDE.md.

Update source/patterns/components/stats-bar/stats-bar.js (create if it
doesn't exist) to listen for the 'surface-map-ready' event and populate
the stats bar DOM when stats are present in event.detail.stats.

The stats bar is rendered by article-header.twig with empty values when
schema_geoshape is set (Twig passes an empty stats array). After the map
fires surface-map-ready with computed stats, JS fills in the values.

Stats format from map.js:
{ distance: 12.7, elev_gain: 2547, elev_loss: 2551,
  elev_min: 811, elev_max: 1595 }

The stats bar elements use these BEM classes:
.stats-bar__item → .stats-bar__value + .stats-bar__unit

The article header needs a data attribute to link it to the map:
data-map-id="{{ map_id }}" on the stats bar container.

Show all files before writing.
```

---

## Step 10 — Build and full test (PENDING)

```bash
lando npm run build
lando drush cr
```

Test cases:
1. Writing post — no geo fields → header + body only, no map
2. Trail post with schema_geoshape only → map + elevation + JS-computed stats
3. Trail post with schema_geoshape + schema_place → track + place marker
4. Trail post with schema_geo only → map centered, no track, no profile
5. Trail post with schema_place only → map with place marker
6. Trail post with manual stats (no geoshape) → Twig stats in header

---

## Step 11 — article.yml Storybook verification (PENDING)

```
Read CLAUDE.md.

Verify source/patterns/collections/article/article.yml has all
required top-level keys:

- header (title, date, category, category_key, difficulty, stats)
- map_id, map_center, map_zoom, map_markers
- map_lines with coords using YAML anchor *track
- geojson_url: null
- drupal_geo_rendered: null
- tiles: 'usgs-topo'
- elev_gain, elev_loss, elev_min, elev_max
- elev_data: *track (YAML anchor)
- body, sidebar_cards

Show changes before writing.
```

---

## Order of execution

1. ✅ Steps 1–7 complete
2. Step 8a — create config files manually
3. Step 8b — Claude Code: routing + form + hook
4. Step 8c — verify admin page
5. Step 9 — stats bar JS
6. Step 10 — build and full test
7. Step 11 — article.yml verification
