# trip_import — Module Reference

Custom Drupal module for importing Google My Maps KMZ exports into Drupal
content. Located at `public_html/modules/custom/trip_import/`.

## What it does

Parses one or more KMZ files from Google My Maps, then creates:

- `geo_entity:poi` — Points of Interest / Sites folder placemarks
- `geo_entity:lodging` — Lodging folder placemarks
- `node:tourist_destination` — Destinations folder placemarks (node, not geo_entity)
- `node:article` — One per Day N route folder, with GeoJSON media + attached geo entities
- `node:tourist_trip` — One per import, collecting all day articles into `schema_itinerary`

All KMZ files are parsed first before any batch ops run, so destinations from
one file are available when articles are created from routes in another file.

## Files

```
trip_import/
├── trip_import.info.yml
├── trip_import.routing.yml        /admin/config/trip-import
├── trip_import.links.menu.yml     Admin > Config > Development
└── src/
    ├── Form/TripImportForm.php    Upload form + batch builder
    └── Batch/TripImportBatch.php  All parse + batch callbacks
```

## KMZ folder conventions

Google My Maps folder names drive what gets imported:

| Folder name pattern | Imported as |
|---|---|
| `Points of Interest` or `Sites` | `geo_entity:poi` |
| `Destinations` | `node:tourist_destination` |
| Starts with `Lodging` | `geo_entity:lodging` |
| `/^Day \d+/i` | Route LineStrings → GeoJSON → `node:article` |

Label cleaning strips leading `Day N:` / `Night N:` prefixes before saving.

## Placemark label conventions (Google My Maps)

For the import to wire geo entities to the correct day articles, label
prefixes must be consistent:

```
Points of Interest: "Day 4: Kilronan Village"   → poi attached to Day 4 article
Destinations:       "Day 5: Galway City"         → tourist_destination, field_day = 5
Destinations:       "Day 5, Day 6: Galway City"  → tourist_destination, field_day = 5 (first)
Lodging:            "Night 5: Hostel Name"        → lodging, field_day = 5, field_nights = 1
Lodging:            "Night 5, Night 6: Hostel"    → lodging, field_day = 5, field_nights = 2
Day folders:        "Day 3"                        → article title from LineString placemark name
```

## Route type detection

LineString placemark name keywords (case-insensitive):

| Route type | Keywords |
|---|---|
| `walking` | walk |
| `cycling` | inis, mór, mor, aran, cycl, bicycl, bike |
| `hiking` | reeks, aonghasa, mountain, hike, hiking, meenabool |
| `driving` | everything else |

Route type drives GeoJSON `properties.route_type`. When multiple routes exist
in a day folder, they're sorted by priority (driving > hiking > cycling >
walking) and the primary route determines the day article's `field_route_type`
taxonomy term.

## Batch operation order

```
1. importPoi         (all files, all pois)
2. importDestination (all files, all destinations)
3. importLodging     (all files, all lodging)
4. createDayArticle  (days ascending — reads results from 1-3)
5. createTrip        (once, last — reads accumulated article IDs)
```

`$context['results']` carries state across batch ops:
- `day_articles[$day]` → nid (used by createTrip)
- `destination_ids[]`  → nids (used by createTrip)

## Field mapping

### node:article created per day

| Article field | Set from |
|---|---|
| `title` | Cleaned LineString placemark name |
| `schema_date_published` | `trip_start_date + (day - 1) days` |
| `schema_geoshape` | `media:data_download` entity wrapping the GeoJSON file |
| `schema_poi` | `geo_entity:poi` with `field_day = N` |
| `schema_destination` | `node:tourist_destination` with `field_day = N` |
| `schema_lodging` | `geo_entity:lodging` with `field_day = N` |
| `field_route_type` | taxonomy_term:route_type, matched by `field_key` |

### node:tourist_destination created per Destination placemark

| Field | Value |
|---|---|
| `title` | Cleaned placemark name |
| `schema_geo` | WKT POINT from KML coordinates (Geofield) |
| `field_day` | First Day number from label prefix |
| `schema_same_as` | URL extracted from KML description (if present) |

### node:tourist_trip created once

| Field | Value |
|---|---|
| `title` | Form input |
| `schema_trip_dates` | Smart Date — value and end_value both set to Unix timestamp of start date |
| `schema_date_published` | Start date string `Y-m-d` |
| `schema_itinerary` | All day article nids, sorted by day number |

Note: `tourist_trip` does **not** have a `schema_destination` field in the
current config. The `createTrip` method attempts to set it — this is a no-op
(guarded by `hasField()`), not an error, but the field needs adding if trip →
destination relationships are required.

## GeoJSON output format

Each route writes to `public://geoshape/<slug>.geojson`:

```json
{
  "type": "FeatureCollection",
  "features": [{
    "type": "Feature",
    "geometry": { "type": "LineString", "coordinates": [[lon, lat], ...] },
    "properties": { "name": "Route Name", "route_type": "cycling" }
  }]
}
```

**Elevation is not extracted.** KML coordinates include altitude (lon,lat,ele)
but `parseKmlCoords` drops the Z value. Only [lon, lat] pairs are written.
This is intentional — elevation comes from the DEM source in `trail_mapper`,
not from Google My Maps.

The file is wrapped in a `media:data_download` entity and attached to the
article via `schema_geoshape`.

## Duplicate detection

| Bundle | Method |
|---|---|
| `geo_entity:poi` | Geofield BETWEEN ±0.001° |
| `geo_entity:lodging` | Geofield BETWEEN ±0.001° |
| `node:tourist_destination` | Title exact match |
| `node:article` | None — always creates |

---

## Known bugs and gaps

### 1. `ray()` debug call left in `createDayArticle` — MUST REMOVE

```php
ray($node);  // line ~170 of TripImportBatch.php
```

This crashes on any environment where the `ray` package is not installed
(Pantheon, CI, any production environment). Remove before deploying.

### 2. Date set twice in `createDayArticle`, second write overwrites the first

```php
// First write — correct per-day date
$date = date('Y-m-d\TH:i:s', strtotime($trip_start_date . " +{$offset} days"));
$node_values = ['schema_date_published' => $date, ...];

// Second write — overwrites with the raw start date for every day
if ($node->hasField('schema_date_published')) {
  $node->set('schema_date_published', $trip_start_date); // always Day 1!
}
```

Remove the second `$node->set('schema_date_published', ...)` block. Every
day article ends up with the trip start date instead of `start + N - 1 days`.

### 3. `tourist_trip` has no `schema_destination` field

`createTrip()` tries `$node->set('schema_destination', ...)` but
`tourist_trip` has no such field in config. The `hasField()` guard prevents
a crash, but destinations are never attached to the trip node. Either:

- Add `schema_destination` (entity_reference → tourist_destination, cardinality
  unlimited) to `tourist_trip`, or
- Remove the dead code from `createTrip()` if trip → destination linking is
  not needed

### 4. `geo_entity:destination` bundle still exists but is not used

The config has a `geo_entity.geo_entity_type.destination` bundle and
`importDestination()` was originally designed for it. The batch now creates
`node:tourist_destination` instead. The `geo_entity:destination` bundle in
config should be retired (or kept for other uses), and any references to it
in views or templates should be audited.

### 5. Multi-route days: only the primary GeoJSON is attached to the article

`$primary_media_id` tracks only the first (`$i === 0`) route media entity.
Subsequent routes in the same day folder are written to disk and a media
entity is created, but they are not referenced from the article. If a day has
both a driving segment and a cycling segment, only the highest-priority one
is attached. This is documented behavior but worth noting if multi-track
days are expected.

### 6. `$route_found = TRUE` prevents multi-route extraction per folder

```php
if ($coords_node && !$route_found) {
    ...
    $route_found = TRUE;  // stops after first LineString
}
```

Only one LineString per Day folder is extracted. If Google My Maps exports
multiple routes in one folder, only the first is used. See item 5.

### 7. Smart Date format for `schema_trip_dates`

`createTrip` sets `end_value` equal to `value` (start timestamp). This gives
a zero-duration trip date. The end date should be computed as
`start + (max day - 1) days`. The max day number is available from
`array_keys($context['results']['day_articles'])`.

---

## Adding route type taxonomy terms

`field_route_type` on `article` references `taxonomy_term:route_type` terms
matched by `field_key`. The vocabulary and terms must exist:

```bash
# After creating the route_type vocabulary with field_key:
lando drush php-eval "
  \$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  foreach (['driving', 'cycling', 'hiking', 'walking'] as $key) {
    \$term = \$storage->create(['vid' => 'route_type', 'name' => ucfirst(\$key)]);
    \$term->set('field_key', \$key);
    \$term->save();
  }
"
```

If no `route_type` terms exist with matching `field_key` values,
`field_route_type` is left empty on all imported articles (silent no-op).

---

## Extending: adding new KMZ folder types

1. Add a detection branch in `parseKmz()` (the `foreach ($folders ...)` loop)
2. Add a batch op callback in `TripImportBatch`
3. Add the op to the `$operations` array in `TripImportForm::submitForm()`
   before `createDayArticle` if the new entities need to be available when
   articles are created

---

## Admin UI

`/admin/config/trip-import` — requires `administer site configuration`

Form fields:
- Trip title → `tourist_trip.title`
- Trip start date → Day 1 date; Day N = start + (N - 1) days
- KMZ files → up to 5 managed_file uploads (temporary://)
- Import options checkboxes → selectively skip entity types

---

## Local dev workflow

```bash
# Enable the module
lando drush en trip_import -y
lando drush cr

# Run an import
# Navigate to /admin/config/trip-import, upload KMZ files, submit

# Check what was created
lando drush php-eval "
  \$nodes = \Drupal::entityQuery('node')
    ->condition('type', ['article', 'tourist_destination', 'tourist_trip'], 'IN')
    ->sort('nid', 'DESC')
    ->range(0, 20)
    ->accessCheck(FALSE)
    ->execute();
  print_r(\$nodes);
"

# Wipe a test import
lando drush php-eval "
  \$nids = \Drupal::entityQuery('node')
    ->condition('type', ['article', 'tourist_destination', 'tourist_trip'], 'IN')
    ->accessCheck(FALSE)->execute();
  \Drupal::entityTypeManager()->getStorage('node')->delete(
    \Drupal::entityTypeManager()->getStorage('node')->loadMultiple(\$nids)
  );
"
```
