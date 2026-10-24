# Trail planner audit (KT), 2026-10-24

Audit of the `public_html/mapping` app (a git submodule) from the zip Sean supplied. Code was read, not run. Where a finding is inferred rather than confirmed, it says so.

Ownership, per the agreed boundaries:
- **TrailMapper project** owns the data, the QGIS pipeline, route stats, and the planner API. Sections 2, 3 and 5 belong there.
- **SeanMontague.com project** owns how the planner appears in Drupal. Section 6 belongs here.

The QGIS Python scripts are audited separately in `trailmapper-pipeline-audit.md`. Section 5 here covers only what the database tables reveal.

## 1. What the app is

| Piece | File | What it does |
|---|---|---|
| Export | `index.php` | Queries `kt_trails` in PostGIS, builds a FeatureCollection in SQL, writes `GeoJSON/KingdomTrails.geojson`, and echoes the SQL and the JSON to the page. |
| Map | `leaflet/index.html`, `js/leafletembed.js`, `js/site.js` | Leaflet 1.5.1 and jQuery 3.4.1 from CDNs. Loads the exported GeoJSON. Clicking segments builds an ordered list with a running distance. |
| Profile | `php_files/loadProfile.php` and `GetProfile.php` | Takes the ordered segment IDs from `$_GET`, works out each segment's direction, pulls precomputed profiles, and returns an SVG with distance, elevation range and climb. |
| GPX | `php_files/exportGPX.php` | Same direction logic, then emits elevation points from `kt_trax` / `kt_trax2` as GPX. |

Data, confirmed from the export and code: 764 segments, all MultiLineString, about 240 mi including roads and VAST. Properties are `id` (type-prefixed, like `mtb1` or `rd373`), `name` (264 distinct), `touches` (a comma-delimited adjacency string) and `length` (metres).

Database: `roundybr_trailmapper` on the **AcuGIS PostgreSQL server**, port 5432. Tables used: `kt_trails`, `kt_junctions`, `kt_profiles` and `kt_profiles2` (forward and reverse), `kt_trax` and `kt_trax2`. The local Lando app connects to that remote database directly. `external_pg` in Drupal points at a different host, a LAN address, so there appear to be **two copies** of the TrailMapper database.

## 2. Security (fix first)

1. **SQL injection.** `loadProfile.php`, `loadProfileAjax.php` and `exportGPX.php` pass `$_GET` values straight into SQL inside `GetProfile` (`WHERE objectid='{$ptId}'`, and the regex `~ ',$objID,'`). Anyone who can reach these URLs can read the database, and possibly write to it, depending on the database user's rights.
2. **Credentials in code.** Database host, user and password are hardcoded in six files: `dbconfig.php`, `index.php`, `loadProfile.php`, `loadProfileAjax.php`, `exportGPX.php` and `getPoints.php`. `getPoints.php` also keeps commented-out credentials for two older databases from the Mountain Scene era, and `dbconfig.php` has a commented line that looks like a password. All of it sits in the submodule's git history. Rotate every one of these that's still valid.
3. **`info.php` runs `phpinfo()`.** If it's reachable, it exposes paths, extensions and environment.
4. **`index.php` writes on GET.** Visiting `/mapping/` runs the export query, overwrites the GeoJSON file and prints the SQL.
5. **Client-side tile keys** for Thunderforest, Jawg and Google Maps are in the JS and HTML. Keys like these are public by nature, but restrict each one by HTTP referrer in its provider's dashboard.

**Check now:** whether `/mapping` is served on production. `curl -sI https://seanmontague.com/mapping/info.php` and `curl -sI https://seanmontague.com/mapping/php_files/loadProfile.php`. Either one returning 200 means items 1, 3 and 4 are live. In that case, deny the directory in `.htaccess` until the planner API replaces it.

## 3. Correctness

1. **Three different distances.** The sidebar total sums `length` (SRID 6589, Vermont State Plane). The profile's "Surface Length" uses `ST_Length(ST_Transform(geom, 26915))`. The profile's x-axis uses the summed widths of the profile geometries. The three won't agree.
2. **Wrong UTM zone (likely).** EPSG:26915 is UTM zone 15N, with its central meridian at 93°W. Kingdom Trails sits near 72°W, about 21° away. At that distance, transverse Mercator scale error is roughly +3.4%, so "Surface Length" likely reads about 3% long. That figure is computed, not measured. To verify, compare `ST_Length(geom)` in 6589 against the 26915 figure for one trail. Use 6589 or a geodesic length (`geom::geography`).
3. **SRID handling is inconsistent.** The export calls `ST_SetSRID(geom, 6589)` before transforming, while `GetProfile` transforms `geom` directly. One of the two assumptions is wrong about the column's SRID. Check it with `SELECT Find_SRID('public','kt_trails','geom');`.
4. **Direction logic is fragile.** `getDirection()` runs about 140 lines of nested loops over comma-delimited junction strings. It re-queries junctions for repeated segments, and it compares whole result sets to detect a reversal. With a node-based topology (section 5), direction becomes "which end of this segment does the previous segment share", which is about 15 lines.
5. **Query count.** Each selected segment triggers about 8 queries: length, junctions (sometimes 3 times), bbox, profile and track. A 20-segment ride runs about 160 round trips to a remote database.
6. **Units are baked in.** Feet and miles are hardcoded in PHP and JS (`3.28083`, `1609.344`, `5280`). That conflicts with the site decision to store and emit metres and convert at display time.
7. **GPX metadata is stale.** It says creator "The Mountain Scene", copyright year 2023, and an OpenStreetMap licence line. Check whether OSM is actually the data source before you publish GPX.
8. **The area switch is a database-name `if`.** `roundybr_trailmapper` maps to the `kt_*` tables, and anything else maps to unprefixed tables. White Mountains needs a real area parameter.

## 4. Dead code and duplication

- **Unused PHP:** `getPoints.php`, `profileClasses2.php`, `GetSVG.php`, `gzip_encode.php` (PHP 4 era), and `loadProfileAjax.php`, which duplicates `loadProfile.php`.
- **Unused pages:** `esri.html`, `google.html`, `svg-google.html`.
- **Legacy data:** `FrontRange.geojson`, `GreenMt.geojson`, `kokopelli.geojson`, `east-haven.geojson`, plus a stale root `KingdomTrails.geojson`.
- **KT GeoJSON copies:** four of them: the root and `GeoJSON/` copies here, plus `trail_mapper/includes/` and `map_page/includes/` in Drupal. They're the same size, but there are at least three different hashes. The map loads the `GeoJSON/` copy.
- **Bundled Leaflet 1.5.1:** about 2.6 MB in `leaflet/js/`, unused because the page loads Leaflet from unpkg.
- **The basemap dropdown does nothing.** `setBasemap()` is a stub. Esri Leaflet is loaded but unused, and its `basemapLayer` is deprecated.

## 5. Data model and pipeline (TrailMapper)

From the tables only. The scripts themselves still need auditing.

| Now | Problem | Better |
|---|---|---|
| `kt_junctions.objectid` holds comma strings, matched with a regex; `start` holds space-separated IDs | No index use, string parsing everywhere, and fragile | A `nodes` table, with `source_node` and `target_node` on each segment (a pgRouting-style topology) |
| `touches` comma strings in the GeoJSON | The same adjacency stored a second time | Derived from nodes when needed |
| `kt_profiles` and `kt_profiles2`, `kt_trax` and `kt_trax2` | Every profile and track stored twice, once per direction | Store one direction. Reverse at query time (`ST_Reverse`, flipped distance), and store per-direction stats as columns (`climb_fwd_m`, `climb_rev_m`, and so on) |
| `kt_` table prefix per area | Copy-paste for each new area | An `area` column (`kt`, `whites`) on shared tables, or one schema per area |
| IDs like `mtb1` and `rd373` | Fine, if they're stable | Confirm the IDs survive a pipeline re-run. Drupal will store them on ride guides |

Recommended pipeline shape. This matches TrailMapper's planned milestone 1: port pipeline steps 1 to 4 to headless Python writing to PostGIS.
- **One entry point** (`python -m trailmapper.pipeline --area kt`) that runs every step in order and is safe to re-run. It works against a staging schema and swaps it in only after validation passes.
- **A validation report at the end of every run:** segment, node and junction counts; total network length; segments with no name; dangling ends; and a per-segment climb diff against the previous run. That account of every change is what makes the pipeline easy to pick back up after time away.
- **A `README` with the exact run order,** the QGIS and PostGIS versions, the CRS used at each step (6589 throughout, unless there's a reason not to), and the DEM source.
- **Credentials from environment variables** or a `.pg_service.conf` / `.pgpass`, never in scripts.
- **One master database.** Decide whether the LAN copy or the AcuGIS copy is authoritative. The pipeline writes to the master, and the other is either a replica or retired.

## 6. Drupal integration (SeanMontague.com)

Consistent with `docs/architecture/decisions.md`: TrailMapper is a separate system, HTTPS is the only link between them, stats come live from TrailMapper and are cached in Drupal, and everything is stored in metres.

**Planner API** (TrailMapper builds it; Drupal consumes it). It replaces `index.php`, `loadProfile.php` and `exportGPX.php`:

| Endpoint | Returns | Replaces |
|---|---|---|
| `GET /areas/{area}/network` | The segment FeatureCollection (id, name, type, length in metres). Static and cacheable | `index.php` and the GeoJSON file |
| `POST /routes/stats` with `{area, segments: [ids]}` | `distance_m`, `climb_m`, `descent_m`, `profile: [[d_m, z_m], ...]`, route geometry, and the direction of each segment | `loadProfile.php` and the three-distance problem |
| `GET /routes/{id}` | A saved curated route, in the same shape | Ride guide stats |
| `GET /routes/{id}/gpx` or `POST /routes/gpx` | A GPX download | `exportGPX.php` |

Parameterised queries only, an API key, and JSON in metres, with formatting left to Drupal. The database is already on AcuGIS, so running the API there, next to it, answers the interface's open "where does the planner run" question for production.

**Drupal side:**
- **A `trailmapper_client` module.** Drupal proxies and caches the API with cache tags. The browser only ever talks to Drupal, so the API key never reaches the client and CORS doesn't come up.
- **A planner page built from existing Surface parts.**
  - The map: `map.js`, already dual-context, with tiles from `drupalSettings.trailMapper`.
  - The profile: `elevation-profile.js`, which already draws a canvas profile from coordinates with Z. Feeding it the planner's `profile` array replaces the server-built SVG.
  - Segment picking and the route list become a new `trail-planner` component with a Storybook fixture. Drop jQuery, and drop `confirm()` dialogs for inline messages.
- **Ride guides.** Store the TrailMapper route ID, plus an optional reference snapshot per the snapshot decision. Render map, profile and stats from the client. Use `[TM: ...]` tokens in copy.
- **Retire after the API is live:**
  - `external_pg`
  - `trail_mapper`'s `GeoJsonGenerator` and `/trail-mapper/generate-geojson`
  - the duplicate `KingdomTrails.geojson` files in `trail_mapper` and `map_page`
  - the `mapping` submodule

## 7. Order of work

1. **Security triage** (section 2). Check whether `/mapping` is live on production, deny it if so, delete `info.php`, rotate credentials.
2. **TrailMapper:** audit the QGIS scripts, pick the master database, and confirm IDs are stable.
3. **TrailMapper:** topology and per-direction stats columns, then the planner API for KT, then White Mountains on the same schema with `area = 'whites'`.
4. **SeanMontague.com:** `trailmapper_client`, then the planner page from Surface components, then ride guide wiring.
5. **Retire** the old app and the Drupal-side duplicates.

## Inputs still needed

- A schema-only dump of the TrailMapper database. Structure, no data, no credentials: `pg_dump -s -h <host> -U <user> roundybr_trailmapper > trailmapper-schema.sql`.
- Whether `/mapping` is deployed on production, and where the submodule's repo is hosted (public or private).
- Which TrailMapper database is the master: the LAN copy or the AcuGIS copy.
