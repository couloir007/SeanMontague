# seanmontague.com — Build Plan

## Current State

**What exists in Drupal (config):**
- `blog_posting` content type — rename to `article` or use as-is
- `place`, `tourist_trip`, `event`, `event_series`, `person`, `web_site`
- Taxonomy: only `tags` — no `category`
- NO `trail_report` — not needed, dropped from model

**What exists in templates:**
- `node--article.html.twig` — needs update for conditional stats/map
- `node--trail-report.html.twig` — delete, no longer needed
- `node--trip.html.twig` — keep, uses `schema_destination`
- `node--place.html.twig` — fixed (uses schema_latitude/schema_longitude)
- Paragraph templates fixed in Step 1

**Pattern files:** all correct, no prefix issues.

---

## Content Model

```
article (BlogPosting)
  ├── body
  ├── schema_date_published    datetime
  ├── schema_category          entity ref → category taxonomy
  ├── schema_place             entity ref → place node (optional)
  ├── schema_audio             entity ref → audio media (optional)
  ├── schema_trip              entity ref → tourist_trip (optional)
  │
  ├── [Trail Stats — Field Group, collapsible]
  ├── schema_distance          decimal
  ├── schema_elev_gain         integer
  ├── schema_elev_loss         integer
  ├── schema_elev_min          integer
  ├── schema_elev_max          integer
  ├── schema_difficulty        list: Easy/Intermediate/Hard/Expert
  └── schema_gpx               file
```

---

## Step 2 — Drupal UI Work

**2a — Add fields to blog_posting**
`/admin/structure/types/blog_posting/fields`
- `schema_date_published` — datetime
- `schema_category` — entity reference → category taxonomy
- `schema_place` — entity reference → place node (optional)
- `schema_audio` — entity reference → audio media (optional)
- `schema_trip` — entity reference → tourist_trip (optional)
- `schema_distance` — decimal
- `schema_elev_gain` — integer
- `schema_elev_loss` — integer
- `schema_elev_min` — integer
- `schema_elev_max` — integer
- `schema_difficulty` — list (text): Easy, Intermediate, Hard, Expert
- `schema_gpx` — file

**2b — Add Field Group for trail stats**
Install Field Group module if not present:
```bash
lando composer require drupal/field_group
lando drush en field_group
```
On blog_posting form display, create a collapsible group called
"Trail Stats" containing the schema_distance, schema_elev_* ,
schema_difficulty, schema_gpx fields.

**2c — Add fields to tourist_trip**
`/admin/structure/types/tourist_trip/fields`
- `body` — text_with_summary
- `schema_date_published` — datetime
- `schema_destination` — entity reference → place (unlimited)
- `schema_itinerary` — entity reference → blog_posting (unlimited)

**2d — Create category taxonomy**
`/admin/structure/taxonomy/add`
- Machine name: `category`, Label: `Category`

Add terms at `/admin/structure/taxonomy/manage/category/add`:
- Writing
- Trails
- Travel
- Permaculture
- Maps
- Tech

**2e — Delete node--trail-report.html.twig**
No longer needed — `blog_posting` handles all article types.

**2f — Export config**
```bash
lando drush csex
git add config/sync
git commit -m "Single article content type with trail stats, category taxonomy"
```

---

## Step 3 — Claude Code Prompts

> CLAUDE.md and pattern files already use correct unprefixed names.

### Prompt 2 — Update node--article.html.twig

```
Read CLAUDE.md. Update templates/content/node--article.html.twig for the
blog_posting content type. It handles all article types — writing, trails,
travel, permaculture — determined by schema_category.

Use @collections/article/article.twig as the base include.

The template should:

1. Always render: title, schema_date_published, schema_category

2. If schema_place is set, pull geo from the referenced Place node using
   node.schema_place.entity.schema_latitude.value and
   node.schema_place.entity.schema_longitude.value for the map center

3. If schema_distance.value is not empty, pass stats array to the header:
   Distance, Elevation Gain, Loss, Min Elev, Max Elev, Difficulty

4. If schema_distance.value is not empty, also pass map_id and map_center
   so article-map-section renders the map and elevation profile

5. Always render body

Show the corrected file before writing.
```

### Prompt 3 — node--blog-posting--card.html.twig

```
Read CLAUDE.md. Create templates/content/node--blog-posting--card.html.twig
that renders a blog_posting node in card view mode using
@components/card/card.twig.

Fields:
- node.label (title)
- node.schema_date_published.value|date('F j, Y')
- node.schema_category.entity.label
- node.schema_category.entity.field_key.value (for CSS modifier)
- content.body|field_value|render|striptags|slice(0, 200) (excerpt)
- node.toUrl().toString() (url)

If schema_distance.value is not empty, append distance to meta:
  e.g. "March 2025 · 12.7 miles"

Show the file before writing.
```

### Prompt 4 — node--event.html.twig

```
Read CLAUDE.md. Create templates/content/node--event.html.twig.

Fields available:
- node.label
- node.schema_event_schedule (date range)
- node.schema_event_type.value
- node.schema_place.entity with schema_latitude.value and
  schema_longitude.value for map center
- content.body|field_value

Use @collections/article-header/article-header.twig for the header.
If schema_place is set, include @components/map/map.twig centered on
the place coordinates with a single marker.

Show the file before writing.
```

### Prompt 5 — node--trip.html.twig review

```
Read CLAUDE.md. Review templates/content/node--trip.html.twig and fix
any issues:

1. It currently uses schema_geo.value.lat — fix to use
   schema_latitude.value and schema_longitude.value on each
   referenced Place node

2. Add @collections/article-header/article-header.twig for the header
   using node.label and node.schema_date_published.value

3. After the map, render content.body|field_value in a prose div

Show the corrected file before writing.
```

---

## Recommended Order

1. ✅ Prompt 1 — fix template bugs (done)
2. Complete Step 2 Drupal UI work
3. Run Prompt 2 — update node--article.html.twig
4. Run Prompt 3 — create card template
5. Run Prompt 4 — create event template
6. Run Prompt 5 — fix trip template
7. `lando drush cr` and test
8. Build Views for /writing and /trails listings
