# Claude Code Next Steps

Reference files (all at project root):
- `CLAUDE.md` — theme architecture, Twig rules, JS standards
- `public_html/themes/custom/surface/CLAUDE.md` — deep-dive theme reference

---

## Current State (April 2026)

### What's done
- Single `article` (BlogPosting) bundle — no trail_report remnants
- All stat/map fields on article: schema_geoshape, schema_geo, schema_place,
  schema_distance, schema_elev_gain/loss/min/max, schema_difficulty, schema_trip
- node--article.html.twig working: map, stats bar, place as center only
- map.js: fitBounds, surface-map-ready event, USGS tiles
- stats-bar.js: populates from Twig (manual fields), no JS computation
- TrailMapperSettingsForm: tile set selector
- GeoShapeConverter: stores GeoJSON in meters
- elevation-profile.js: renders from GeoJSON Z values

### What's missing / needs doing
- `activity_type` vocabulary does not exist yet
- `field_key` field does not exist on any taxonomy (not on category either)
- `schema_activity_type` field not on article bundle
- `elevation_unit` not in trail_mapper.settings.yml
- Pathauto using generic schemadotorg pattern — no custom hook
- CLAUDE.md still references old two-bundle model

---

## Step 1 — activity_type vocabulary + field_key (PENDING)

### 1a — Manual: create vocabulary and field via UI

1. Admin → Structure → Taxonomy → Add vocabulary
   - Name: Activity Type, machine name: activity_type

2. Admin → Structure → Taxonomy → Activity Type → Manage fields
   - Add field: Text (plain), machine name field_key, label "Key", max 32 chars

3. Add terms:

   Mountain Biking → bike
   Hiking          → hike
   Skiing          → ski
   Telemark        → ski
   Permaculture    → permaculture

4. Also add field_key to the category vocabulary (same field storage
   can be shared):
   - Admin → Structure → Taxonomy → Category → Manage fields
   - Add existing field: field_key

5. Populate category terms:

   Trails       → trails
   Drupal       → drupal
   Permaculture → permaculture
   Maps         → maps

### 1b — Export config

```bash
lando drush cex
git add config/sync
git commit -m "Add activity_type vocabulary and field_key to taxonomies"
```

---

## Step 2 — schema_activity_type field on article (PENDING)

Add the activity type reference field to the article bundle.

```
Read CLAUDE.md.

Add field schema_activity_type to the article node bundle:
- Field type: entity_reference → taxonomy_term
- Target bundle: activity_type
- Label: Activity Type
- Machine name: schema_activity_type
- Cardinality: 1
- Not required

Use drush field:create or schemadotorg module if available.
Show the command before running.

After creating:
lando drush cex
git add config/sync
git commit -m "Add schema_activity_type field to article bundle"
```

---

## Step 3 — elevation_unit in trail_mapper settings (PENDING)

The TrailMapperSettingsForm PHP already has the elevation_unit field
but the config schema and default config files are missing it.

### 3a — Manual: update config files

Update public_html/modules/custom/trail_mapper/config/schema/trail_mapper.schema.yml
to add elevation_unit mapping (type: string, label: 'Elevation display unit').

Update public_html/modules/custom/trail_mapper/config/install/trail_mapper.settings.yml
to add: elevation_unit: feet

### 3b — Claude Code: add elevation_unit to hook_page_attachments

```
Read CLAUDE.md.

In public_html/modules/custom/trail_mapper/trail_mapper.module, update
trail_mapper_page_attachments() to include elevationUnit in drupalSettings:

'elevationUnit' => TrailMapperSettingsForm::elevationUnit(),

Show the current function before making changes.
After: lando drush cr
```

---

## Step 4 — Pathauto hook (PENDING)

Requires Steps 1 and 2 complete (activity_type vocab + field_key +
schema_activity_type on article).

Target alias patterns:
- TouristTrip       → /trips/[title]
- Article + trip    → /trips/[trip-title]/[article-title]
- Article + activity_type → /trails/[activity_key]/[article-title]
- Article + category drupal → /drupal/[article-title]
- Article + category permaculture → /permaculture/[article-title]
- Article fallback  → /writing/[article-title]

```
Read CLAUDE.md.

In public_html/modules/custom/trail_mapper/trail_mapper.module,
implement hook_pathauto_pattern_alter() and supporting token hooks.

Logic — evaluate in order, use first match:

1. Bundle tourist_trip → pattern: /trips/[node:title]

2. Bundle article + schema_trip not empty
   → pattern: /trips/[node:schema_trip:entity:title]/[node:title]

3. Bundle article + schema_activity_type not empty
   → Load term, get field_key value
   → pattern: /trails/FIELD_KEY/[node:title]
   → Use hook_token_info() + hook_tokens() to expose [node:activity-key]
      as a custom token, OR set the pattern string directly in PHP

4. Bundle article + schema_category field_key = 'drupal'
   → pattern: /drupal/[node:title]

5. Bundle article + schema_category field_key = 'permaculture'
   → pattern: /permaculture/[node:title]

6. Bundle article fallback → pattern: /writing/[node:title]

Use $variables['pattern']->setPattern().
Show full implementation before writing.

After:
lando drush cr
lando drush pathauto:aliases-generate
```

---

## Step 5 — Update CLAUDE.md (PENDING)

Do this last — captures reality after all other steps are complete.

```
Read CLAUDE.md.

Update the content model section:

1. Remove all references to trail_report bundle and the
   "Why two BlogPosting bundles" explanation.

2. Update the article (BlogPosting) field list to match reality:
   body, title, schema_date_published,
   schema_category, schema_activity_type, schema_trip,
   schema_place, schema_geo, schema_geoshape,
   schema_distance, schema_elev_gain/loss/min/max,
   schema_difficulty, schema_audio, field_image

3. Add note: stat fields are optional — only relevant for trail/ski
   articles. Stats bar renders from Twig when schema_distance is set.

4. Update surface-map-ready event doc: stats are NOT dispatched.
   Stats come from Twig manual fields only.

5. Update Common Commands — remove any trail_report bundle references.

Show diff before writing.
```

---

## Order of execution

1. Step 1a — Create activity_type vocab + field_key (manual UI)
2. Step 1b — Export config
3. Step 2 — Add schema_activity_type to article (Claude Code)
4. Step 3a — Update trail_mapper config schema files (manual)
5. Step 3b — Update hook_page_attachments (Claude Code)
6. Step 4 — Pathauto hook (Claude Code — requires Steps 1+2)
7. Step 5 — Update CLAUDE.md (Claude Code — do last)

---

## Step 0 — Organize article form display and view displays (PENDING)

Do this before all other steps — it's config-only and unblocked.

### 0a — Form display

```
Read CLAUDE.md.
Read config/sync/core.entity_form_display.node.article.default.yml.

Reorganize the article form display field groups as follows.

Keep all existing field_group types and format_settings. Only change
group membership, weights, and labels where noted.

Group: Content (details, open, weight 0)
  - title
  - schema_date_published
  - schema_category
  - body

Group: Location & Trip (details, open, weight 1)
  - schema_place
  - schema_geo       ← move from Trail Stats
  - schema_trip

Group: Trail Stats (details, collapsed, weight 2)
  description: 'Fill in for trail and ski reports. Leave empty for writing posts.'
  - schema_difficulty
  - schema_geoshape
  - schema_distance
  - schema_elev_gain
  - schema_elev_loss
  - schema_elev_min
  - schema_elev_max

Group: Audio (details, collapsed, weight 3)
  - schema_audio

Group: SEO & Links (details, collapsed, weight 4)
  - schema_topic
  - schema_related_link
  - schema_significant_link
  - field_metatag

Group: Editorial (details_sidebar, weight 5)
  - field_editorial

Note: schema_activity_type does not exist yet — it will be added to
the Content group in a later step.

Show the full updated yml before writing.
After: lando drush cim && lando drush cr
```

### 0b — View display: default

```
Read CLAUDE.md.
Read config/sync/core.entity_view_display.node.article.default.yml.

The node--article.html.twig template handles all rendering. It accesses
fields directly via node.* and passes values explicitly to the article
collection. The view display formatters are largely bypassed.

CRITICAL: schema_geo must be hidden. The leaflet_formatter_default it
currently uses would render a second Drupal Leaflet map on the page.
map.js handles all Leaflet rendering — the Leaflet module formatter
must not output anything on article pages.

Update the default view display:

Visible (template accesses via content.* render array):
  - body: text_default, label hidden, weight 0

Hidden (template accesses directly via node.* or field not needed):
  - schema_geo        ← CRITICAL — hide this
  - schema_place
  - schema_trip
  - schema_category
  - schema_date_published
  - schema_difficulty
  - schema_distance
  - schema_elev_gain
  - schema_elev_loss
  - schema_elev_min
  - schema_elev_max
  - schema_geoshape
  - schema_audio
  - schema_topic
  - schema_related_link
  - schema_significant_link
  - field_editorial
  - field_metatag

Show the full updated yml before writing.
After: lando drush cim && lando drush cr
```

### 0c — View display: teaser

```
Read CLAUDE.md.
Read config/sync/core.entity_view_display.node.article.teaser.yml.

The teaser display is used for article cards in listings and views.
Currently it shows almost nothing (just links). Build it out for cards.

Visible:
  - field_image: image, label hidden, image style 'card' (or 'medium'
    if card style does not exist), weight 0
  - schema_date_published: datetime_default, label hidden,
    format_type: html_date, weight 1
  - schema_category: entity_reference_label, label hidden,
    link false, weight 2
  - schema_difficulty: list_default, label hidden, weight 3
  - body: text_summary_or_trimmed, label hidden, trim_length 180,
    weight 4
  - links: weight 5

Hidden (everything else):
  - schema_geo, schema_place, schema_trip, schema_geoshape,
    schema_distance, schema_elev_*, schema_audio, schema_topic,
    schema_related_link, schema_significant_link,
    field_editorial, field_metatag

Show the full updated yml before writing.
After: lando drush cim && lando drush cr
```

