# Claude Code Next Steps

Reference: read the **root CLAUDE.md** for Drupal/config/module work.
Read **public_html/themes/custom/surface/CLAUDE.md** for theme/Twig/JS work.

---

## Status (April 2026)

| Step | Task | Status |
|---|---|---|
| 0a | Article form display — reorganize groups | ❌ pending |
| 0b | Article view default — hide fields, kill Leaflet formatter | ✅ done |
| 0c | Article view teaser — add field_image | ❌ pending |
| 0d | Template bug — place geo fields in node--article | ❌ pending |
| 1  | View/form displays — place, tourist_trip, event, event_series | ❌ pending |
| 2  | field_key on category + activity_type taxonomies | ❌ pending |
| 3  | schema_activity_type field on article | ✅ done |
| 4  | elevation_unit in trail_mapper settings | ❌ pending |
| 5  | Pathauto custom hook | ❌ blocked on Step 2 |
| 6  | CLAUDE.md update | ✅ done |

---

## Template context — which fields are accessed how

Article, Place, and TouristTrip all have custom templates that access
fields directly via `node.*` and use `content.body|field_value` for body.
Their view displays should have only `body` visible.

Event and EventSeries have NO custom templates — Drupal's default field
rendering IS the output. Their view displays must show the right fields
with the right formatters.

---

## Step 0a — Article form display: reorganize groups (PENDING)

```
Read the root CLAUDE.md.
Read config/sync/core.entity_form_display.node.article.default.yml.

Reorganize field groups. Keep all existing format_type and format_settings.
Only change group membership and weights.

Group: Content (details, open, weight 0)
  children: title, schema_date_published, schema_category,
            schema_activity_type, body

Group: Location & Trip (details, open, weight 1)
  children: schema_place, schema_geo, schema_trip

Group: Trail Stats (details, collapsed, weight 2)
  description: 'Fill in for trail and ski reports. Leave empty for writing posts.'
  children: schema_difficulty, schema_geoshape, schema_distance,
            schema_elev_gain, schema_elev_loss, schema_elev_min,
            schema_elev_max

Group: Audio (details, collapsed, weight 3)
  children: schema_audio

Group: SEO & Links (details, collapsed, weight 4)
  children: schema_topic, schema_related_link, schema_significant_link,
            field_metatag

Group: Editorial (details_sidebar, weight 5)
  children: field_editorial

Changes from current state:
- schema_geo moves from Trail Stats → Location & Trip
- schema_activity_type added to Content group
- schema_geoshape replaces it in Trail Stats

Show the full updated yml before writing.
After: lando drush cim && lando drush cr
```

---

## Step 0c — Article view teaser: add field_image (PENDING)

```
Read the root CLAUDE.md.
Read config/sync/core.entity_view_display.node.article.teaser.yml.

Current visible: body, links, schema_category, schema_date_published,
schema_difficulty. Missing field_image.

Add field_image as the first visible field:
  - field_image: image formatter, label hidden, image_style 'medium',
    link_type 'content', weight 0

Adjust weights so remaining fields follow: schema_date_published (1),
schema_category (2), schema_difficulty (3), body (4), links (5).

Show the full updated yml before writing.
After: lando drush cim && lando drush cr
```

---

## Step 0d — Template bug: place geo in node--article (PENDING)

```
Read the root CLAUDE.md.
Read public_html/themes/custom/surface/templates/content/node--article.html.twig.

BUG: Template references place.schema_geo.value.lat/.lon but Place uses
separate decimal fields schema_latitude and schema_longitude (not a
Geofield). has_place always evaluates false.

Fix:
{% set has_place = place and place.schema_latitude.value is not empty %}

{% elseif has_place %}
  {% set map_center = place.schema_latitude.value ~ ',' ~ place.schema_longitude.value %}

Show current file then fixed version before writing.
After: lando drush cr
```

---

## Step 1 — Display config: place, tourist_trip, event, event_series (PENDING)

### 1a — Place view displays

```
Read the root CLAUDE.md.
Read config/sync/core.entity_view_display.node.place.default.yml.
Read config/sync/core.entity_view_display.node.place.teaser.yml.

node--place.html.twig uses content.body|field_value and accesses all
other fields directly via node.*. The view display drives only body.

place.default — set visible:
  - body: text_default, label hidden, weight 0
  Set hidden: schema_latitude, schema_longitude, schema_address,
  schema_image, schema_telephone, schema_related_link,
  schema_significant_link, schema_subject_of, field_editorial, links

place.teaser — set visible:
  - schema_image: image formatter, label hidden, image_style medium, weight 0
  - schema_address: address_plain, label hidden, weight 1
  - body: text_summary_or_trimmed, label hidden, trim_length 120, weight 2
  - links: weight 3
  Set hidden: schema_latitude, schema_longitude, schema_telephone,
  schema_related_link, schema_significant_link, schema_subject_of,
  field_editorial

Show both updated ymls before writing.
After: lando drush cim && lando drush cr
```

### 1b — TouristTrip form + view displays

```
Read the root CLAUDE.md.
Read config/sync/core.entity_form_display.node.tourist_trip.default.yml.
Read config/sync/core.entity_view_display.node.tourist_trip.default.yml.
Read config/sync/core.entity_view_display.node.tourist_trip.teaser.yml.

FORM DISPLAY — tourist_trip is missing most fields from groups.
Current group_general has only schema_image. body, schema_date_published,
schema_destination, schema_itinerary are ungrouped.

Reorganize form display groups:

Group: Content (details, open, weight 0)
  children: title, schema_date_published, schema_image, body

Group: Destinations & Itinerary (details, open, weight 1)
  children: schema_destination, schema_itinerary

Group: Links (details, collapsed, weight 2)
  children: schema_related_link, schema_significant_link

Group: References (details, collapsed, weight 3)
  children: schema_subject_of

Group: Editorial (details_sidebar, weight 4)
  children: field_editorial

VIEW DISPLAY DEFAULT — node--trip.html.twig uses content.body|field_value
only. body is currently HIDDEN — fix this.

tourist_trip.default — set visible:
  - body: text_default, label hidden, weight 0
  Set hidden: schema_image, schema_destination, schema_itinerary,
  schema_date_published, schema_related_link, schema_significant_link,
  schema_subject_of, field_editorial, links

tourist_trip.teaser — set visible:
  - schema_image: image formatter, label hidden, image_style medium, weight 0
  - schema_date_published: datetime_default, label hidden,
    format_type html_date, weight 1
  - body: text_summary_or_trimmed, label hidden, trim_length 150, weight 2
  - links: weight 3
  Set hidden: schema_destination, schema_itinerary, schema_related_link,
  schema_significant_link, schema_subject_of, field_editorial

Show all three updated ymls before writing.
After: lando drush cim && lando drush cr
```

### 1c — Event + EventSeries view displays

```
Read the root CLAUDE.md.
Read config/sync/core.entity_view_display.node.event.default.yml.
Read config/sync/core.entity_view_display.node.event.teaser.yml.
Read config/sync/core.entity_view_display.node.event_series.default.yml.
Read config/sync/core.entity_view_display.node.event_series.teaser.yml.

No custom Twig templates exist for event or event_series — Drupal's field
rendering is the output. Show only fields that are meaningful to visitors.

event.default — set visible:
  - schema_image: image, label hidden, image_style large, weight 0
  - schema_event_type: list_default, label: "Type", weight 1
  - schema_event_schedule: daterecur_basic_table, label: "When", weight 2
  - body: text_default, label hidden, weight 3
  Set hidden: schema_about, schema_related_link, schema_significant_link,
  schema_subject_of, field_editorial, links

event.teaser — set visible:
  - schema_image: image, label hidden, image_style medium, weight 0
  - schema_event_type: list_default, label hidden, weight 1
  - schema_event_schedule: daterecur_basic_table, label hidden, weight 2
  - body: text_summary_or_trimmed, label hidden, trim_length 120, weight 3
  - links: weight 4
  Set hidden: schema_about, schema_related_link, schema_significant_link,
  schema_subject_of, field_editorial

event_series.default — set visible:
  - schema_image: image, label hidden, image_style large, weight 0
  - schema_event_schedule: daterecur_basic_table, label: "Schedule", weight 1
  - body: text_default, label hidden, weight 2
  Set hidden: schema_about, schema_related_link, schema_significant_link,
  schema_subject_of, field_editorial, links

event_series.teaser — set visible:
  - schema_image: image, label hidden, image_style medium, weight 0
  - schema_event_schedule: daterecur_basic_table, label hidden, weight 1
  - body: text_summary_or_trimmed, label hidden, trim_length 120, weight 2
  - links: weight 3
  Set hidden: schema_about, schema_related_link, schema_significant_link,
  schema_subject_of, field_editorial

Show all four updated ymls before writing.
After: lando drush cim && lando drush cr
```

---

## Step 2 — field_key on taxonomy terms (PENDING)

### 2a — Manual: create field via UI

1. Admin → Structure → Taxonomy → Category → Manage fields
   Add field: Text (plain), machine name field_key, label "Key", max 32 chars

2. Admin → Structure → Taxonomy → Activity Type → Manage fields
   Add existing field: field_key (reuse same storage)

3. Populate category terms:
   Trails → trails | Drupal → drupal | Permaculture → permaculture | Maps → maps

4. Populate activity_type terms:
   Mountain Biking → bike | Hiking → hike | Skiing → ski | Telemark → ski
   Permaculture → permaculture

### 2b — Export config

```bash
lando drush cex
git add config/sync
git commit -m "Add field_key to category and activity_type taxonomies"
```

---

## Step 4 — elevation_unit in trail_mapper (PENDING)

### 4a — Manual: update module config files

In public_html/modules/custom/trail_mapper/config/schema/trail_mapper.schema.yml
add under mapping:
  elevation_unit:
    type: string
    label: 'Elevation display unit'

In public_html/modules/custom/trail_mapper/config/install/trail_mapper.settings.yml
add:
  elevation_unit: feet

### 4b — Claude Code: hook_page_attachments

```
Read the root CLAUDE.md.

In public_html/modules/custom/trail_mapper/trail_mapper.module, find
trail_mapper_page_attachments() and add to the drupalSettings trailMapper
object:

  'elevationUnit' => \Drupal::config('trail_mapper.settings')
    ->get('elevation_unit') ?? 'feet',

Show the current function before making changes.
After: lando drush cr
```

---

## Step 5 — Pathauto custom hook (PENDING — requires Step 2)

Target alias patterns:
- TouristTrip → /trips/[title]
- Article + schema_trip → /trips/[trip-title]/[article-title]
- Article + schema_activity_type → /trails/[activity_key]/[article-title]
- Article + category key drupal → /drupal/[article-title]
- Article + category key permaculture → /permaculture/[article-title]
- Article fallback → /writing/[article-title]

```
Read the root CLAUDE.md.

In public_html/modules/custom/trail_mapper/trail_mapper.module implement
hook_pathauto_pattern_alter(). Evaluate in order, use first match:

1. Bundle tourist_trip → /trips/[node:title]
2. Bundle article, schema_trip not empty
   → /trips/[node:schema_trip:entity:title]/[node:title]
3. Bundle article, schema_activity_type not empty
   → load term, get field_key → /trails/{key}/[node:title]
   Use hook_token_info() + hook_tokens() for [node:activity-key] token
   OR resolve the key in PHP and set pattern string directly.
4. Bundle article, schema_category field_key = drupal → /drupal/[node:title]
5. Bundle article, schema_category field_key = permaculture
   → /permaculture/[node:title]
6. Bundle article fallback → /writing/[node:title]

Use $variables['pattern']->setPattern().
Show full implementation before writing.

After:
lando drush cr
lando drush pathauto:aliases-generate
```

---

## Execution order

0a → 0c → 0d → 1a → 1b → 1c → 2a → 2b → 4a → 4b → 5
