# trailmapper/ — TrailMapper web planner

This directory is the TrailMapper web planner (KT trail planner back end). It
is owned by the TrailMapper project, not the Drupal site. It sits outside the
docroot on purpose: only thin entry points in `public_html/mapping/php_files/`
reach it.

## Rules

- **Drupal code must never `require` anything from here or connect to this
  database.** The only link between Drupal and TrailMapper is HTTPS (see
  `docs/architecture/decisions.md`).
- **Credentials live only in `config/settings.php`** (gitignored). Copy
  `config/settings.example.php` to create it. Never hardcode them elsewhere,
  and never print them.
- All SQL uses PDO prepared statements. Segment ids must match
  `^[a-z]+[0-9]+$` (HTTP 400 otherwise); table names come from the fixed area
  map in `GetProfile::AREAS`, never from input.

## Layout

| Path | Purpose |
|---|---|
| `src/db.php` | Returns a PDO pgsql connection from `config/settings.php` |
| `src/GetProfile.php` | Direction logic, SVG profile, and GPX track data |
| `bin/export-geojson.php` | CLI only. Writes `public_html/mapping/GeoJSON/KingdomTrails.geojson` |
| `config/` | `settings.example.php` (committed), `settings.php` (gitignored) |

Public entry points: `public_html/mapping/php_files/loadProfile.php` and
`exportGPX.php`. Regenerate the GeoJSON with
`lando php trailmapper/bin/export-geojson.php`.

This is the interim planner. The planned replacement is the TrailMapper
planner API (`docs/integrations/trail-planner-audit.md`, section 6).
