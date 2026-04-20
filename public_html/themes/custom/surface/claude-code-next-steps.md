# Claude Code Next Steps

Reference: read the **root CLAUDE.md** for Drupal/config/module work.
Read **public_html/themes/custom/surface/CLAUDE.md** for theme/Twig/JS work.
Read **public_html/themes/custom/surface/STORYBOOK.md** before any source/ changes.
Follow the GVP pattern in `public_html/modules/custom/gvp_schemadotorg/` for Schema.org hooks.
All custom modules use `package: Sean Montague`.

---

## Status (April 2026)

| Step | Task | Status |
|---|---|---|
| 0e | Smart Date on tourist_trip + Schema.org dates hook | ✅ done |
| 0f-i | field_map_tiles on article | ✅ done |
| 0f-ii | Tile sets in trail_mapper + map.js | ✅ done |
| 0g-i | DataDownload media type | ✅ done |
| 0g-ii | GeoShapeConverter ✅ — node--article.html.twig template | ❌ pending |
| 0g-iii | GPX waypoint → POI extraction | ❌ optional |
| 0g-iv | spatialCoverage Schema.org track | ❌ optional |
| 1a | geo_entity destination bundle | ✅ done |
| 1b | seanmontague_schemadotorg module | ✅ done |
| 2a | route_type styling fields | ❌ pending — manual UI |
| 2b | field_key on category + activity_type | ✅ done (field_field_key) |
| 3a | leaflet_full_page refactor → map_page | ✅ done |
| 3b | seanmontague_map module | ✅ done |
| 4a | schema_date_published on tourist_trip | ✅ done |
| 4b | Schema.org fixes | ✅ done |
| M | Module package rename + removals | ✅ done |
| N | Rename leaflet_full_page → map_page | ✅ done |
| 5 | KMZ importer with admin UI | ❌ pending — Claude Code |

---

## Step 0g-ii — node--article.html.twig template fix (PENDING — Claude Code)

GeoShapeConverter already traverses the data_download media entity via
`field_media_file` correctly. Only the Twig template needs updating.

```
Read the root CLAUDE.md.
Read public_html/themes/custom/surface/templates/content/node--article.html.twig.

schema_geoshape is now an entity reference to data_download media.
The file is at: schema_geoshape → media entity → field_media_file → file entity.

Fix 1 — geojson_url (line ~71):
  was: node.schema_geoshape.entity.uri.value|file_url
  now: node.schema_geoshape.entity.field_media_file.entity.fileuri|file_url

Fix 2 — GPX download link. Add after geojson_url:
  {% set geo_media = node.schema_geoshape.entity %}
  {% set geo_file  = geo_media ? geo_media.field_media_file.entity : null %}
  {% set is_gpx    = geo_file and geo_file.filename matches '/\\.gpx$/i' %}
  Note: matches must be applied to geo_file.filename (a string),
  never to the field object itself.

Fix 3 — update comment on line 7:
  was: schema_geoshape (file)
  now: schema_geoshape (data_download media)

Show the full template before writing. After: lando drush cr
```

---

## Step 2a — route_type styling fields (PENDING — manual UI)

`route_type` vocabulary and `field_route_type` on article already exist.
Add styling fields to route_type terms so each term is self-describing.
`seanmontague_map` and `map.js` read these fields directly — no code
change needed when adding new route types later.

1. Admin → Structure → Taxonomy → Route Type → Manage fields
   Add three fields:
   - `field_color` — Text (plain), label "Color", max 7 chars (#rrggbb)
   - `field_dash_pattern` — List (text), label "Dash Pattern"
     Allowed values: solid|Solid, dashed|Dashed, dotted|Dotted
   - `field_line_weight` — Number (integer), label "Line Weight", default 3

2. Populate term values:

   | Term    | field_color | field_dash_pattern | field_line_weight |
   |---------|-------------|-------------------|-------------------|
   | Driving | #736e62     | dashed            | 2                 |
   | Walking | #3a5a40     | solid             | 3                 |
   | Hiking  | #7a3410     | solid             | 3                 |
   | Cycling | #4a7c9e     | solid             | 3                 |

3. Export config and commit:
   ```bash
   lando drush cex
   git add config/sync
   git commit -m "Add styling fields to route_type taxonomy"
   ```

---

## Step 5 — KMZ importer with admin UI (PENDING — Claude Code)

```
Read the root CLAUDE.md.

Create public_html/modules/custom/ireland_import/ with:

ireland_import.info.yml:
  name: 'Ireland Trip Importer'
  package: 'Sean Montague'
  description: 'Admin UI to import KMZ files as geo_entity records
    and GeoJSON route files for the Ireland April 2024 trip.'

Admin form at /admin/config/ireland-import:
  - File upload for KMZ file
  - Checkboxes: Import Sites (POIs), Import Lodging (Destinations),
    Extract Routes (GeoJSON)
  - Submit → batch import

KML parsing (KMZ is a zipped KML — unzip in memory):

SITES folder → geo_entity:poi records
  label = placemark name
  schema_geo = Geofield WKT point from coordinates
  Skip if poi with same label already exists.

Lodging folder → geo_entity:destination records
  label = simplified name (strip address cruft after comma)
  schema_geo = Geofield WKT point

Day folders (Day 1, Day 2 etc) → GeoJSON LineString files
  saved to public://geoshape/{slug}.geojson
  Route type detection from folder name:
    contains "Walking" or "Inis" → walking
    contains "Reeks" or "Dún"    → hiking
    otherwise                    → driving
  Output a results summary table.

KMZ files are at:
  public_html/project/Ireland_Trip_Day_1-5_Final.kmz
  public_html/project/Ireland_Trip_Day_6___7_Final.kmz

Show full implementation before writing.
After: lando drush en ireland_import && lando drush cr
Access at: /admin/config/ireland-import
```

---

## Notes

**field_field_key** — machine name has double prefix. Use in Twig:
`node.schema_category.entity.field_field_key.value`

**schema_trip_dates** cardinality 2:
- item 0 = arrivalTime (arrived at destination)
- item 1 = departureTime (departed destination)

**geo_entity field access:**
- POI/destination: `entity.schema_geo.lat` / `entity.schema_geo.lon`
- Place node: `node.schema_latitude.value` / `node.schema_longitude.value`

---

## URL Aliases (manual)

| Content | Pattern |
|---|---|
| Trail report | `/trails/bike/[title]`, `/trails/hike/[title]`, `/trails/ski/[title]` |
| Trip | `/trips/[title]` |
| Trip article | `/trips/[trip-title]/[article-title]` |
| Drupal post | `/drupal/[title]` |
| Permaculture | `/permaculture/[title]` |
| Writing | `/writing/[title]` |
| Point of Interest | `/places/[title]` |

---

## Execution order

Manual first: **2a**
Claude Code: **0g-ii → 5**
Optional: **0g-iii → 0g-iv**
