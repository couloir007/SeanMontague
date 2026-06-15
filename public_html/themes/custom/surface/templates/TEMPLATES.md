# Surface Theme — Templates Reference

Templates live in `public_html/themes/custom/surface/templates/` and map
Drupal's field API to Surface pattern collections. They contain **zero
presentation logic** — layout, CSS, and HTML live entirely in the pattern
files under `source/patterns/`.

---

## Directory structure

```
templates/
├── block/
│   ├── block.html.twig
│   ├── block--facet-block.html.twig
│   ├── block--local-tasks-block.html.twig
│   ├── block--menu-block--sidebar.html.twig
│   ├── block--page-title-block.html.twig
│   ├── block--system-breadcrumb-block.html.twig
│   ├── block--system-menu-block.html.twig
│   └── block--system-messages-block.html.twig
├── content/
│   ├── node.html.twig
│   ├── node--article.html.twig            # article + trail_report → @layouts/article
│   ├── node--tourist-trip.html.twig       # tourist_trip → @collections/trip  ✅ active
│   ├── node--trip.html.twig               # ⚠️ LEGACY — do not use, delete it
│   ├── node--place.html.twig              # ⚠️ geo fields need updating
│   ├── page-title.html.twig
│   └── taxonomy-term.html.twig
├── content-edit/                          # admin UI form overrides
├── custom/
│   ├── page--403.html.twig
│   └── page--404.html.twig
├── field/
│   ├── field.html.twig
│   ├── field--field-content.html.twig
│   ├── field--field-geo.html.twig
│   ├── field--node--field-image.html.twig
│   └── field--node--title.html.twig
├── form/                                  # form element overrides
├── layout/
│   ├── html.html.twig
│   ├── page.html.twig
│   ├── page--leaflet-full-page.html.twig
│   ├── layout--section-bare.html.twig
│   ├── layout--section-two-col.html.twig
│   ├── layout--onecol.html.twig
│   ├── layout--twocol.html.twig           # + 30-70, 70-30, 30-70-menu variants
│   ├── layout--threecol.html.twig         # + 25-25-50, 25-50-25, 50-25-25 variants
│   ├── layout--fourcol.html.twig
│   ├── layout--sixcol.html.twig
│   └── region*.html.twig
├── media/
│   ├── media.html.twig
│   ├── media--image.html.twig
│   └── media--image--image-url.html.twig
├── navigation/                            # menu, breadcrumb, pager
├── paragraphs/
│   ├── paragraph--place.html.twig         # ⚠️ geo fields need updating
│   ├── paragraph--trail-report.html.twig  # ⚠️ image traversal bug
│   └── paragraph--trip.html.twig          # ⚠️ LEGACY — schema_destination removed
└── views/
```

---

## Universal rules

### `|field_value` strips Drupal wrappers — always use it

```twig
{# CORRECT #}
{{ content.field_body|field_value }}
{{ content.body|field_value|render }}

{# WRONG — outputs full <div class="field field--..."> wrapper markup #}
{{ content.field_body }}
```

### `{{ attributes }}` required on paragraph outer elements

Layout Paragraphs edit controls live on this object. Drop it and the LP
edit UI breaks silently.

```twig
{# CORRECT #}
<div{{ attributes }}>
  <div class="prose">{{ content.field_body|field_value }}</div>
</div>

{# WRONG #}
<div class="prose">{{ content.field_body|field_value }}</div>
```

### `only` on every collection/component include

Prevents Drupal context variables (`node`, `content`, `attributes`…) from
leaking into the pattern scope and causing unexpected rendering.

```twig
{% include '@layouts/article/article.twig' with { ... } only %}
```

### Smart Date — use `[0]`, never `.first`

`.first` is blocked by Drupal's Twig sandbox on `SmartDateFieldItemList`.
This will throw a `SecurityError` with no obvious indication why.

```twig
{# CORRECT #}
{% set item  = node.schema_trip_dates[0] %}
{% set start = item.value %}      {# Unix timestamp #}
{% set end   = item.end_value %}

{# WRONG — SecurityError: Calling "first" method is not allowed #}
{% set item = node.schema_trip_dates.first %}
```

### `matches` — apply to scalar `.value`, never to a field object

```twig
{# CORRECT — .value unwraps FieldItemList to a string scalar #}
{% set is_gpx = geo_file and geo_file.filename.value matches '/\\.gpx$/i' %}

{# WRONG — filename is a FieldItemList, not a string #}
{% set is_gpx = geo_file.filename matches '/\\.gpx$/i' %}
```

---

## Field access quick reference

| Field type | Correct access | Notes / common mistakes |
|---|---|---|
| Text / string field | `node.field_name.value` | Always `.value` |
| Body field | `content.body\|field_value\|render` | Strip wrapper with `\|field_value` |
| Field body | `content.field_body\|field_value\|render` | Same — `field_body` not `body` |
| Entity label | `entity.label.value` | String |
| Node title string | `node.label.value` | Equivalent to `node.title.value` |
| File URI | `file.fileuri\|file_url` | `.fileuri` is a property, not a field |
| File name | `file.filename.value` | IS a field — needs `.value` |
| **Geofield on geo_entity** | `entity.schema_geo.lat` / `.lon` | **Direct floats — no `.value`** |
| Geofield on node | `node.schema_geo.lat.value` | `.value` used here — see note |
| Smart Date start | `node.field_dates[0].value` | Unix timestamp; `[0]` not `.first` |
| Smart Date end | `node.field_dates[0].end_value` | |
| Media → file URI | `node.schema_image.entity` `.field_media_image.entity.fileuri\|file_url` | Two entity hops |
| Taxonomy label | `node.schema_category.entity.label.value` | String |
| Taxonomy text field | `node.schema_category.entity.field_key.value` | IS a field — needs `.value` |
| Address sub-field | `entity.schema_address.administrative_area` | Direct property on address item |
| Boolean | `node.field_show.value` | Returns `'1'` or `'0'` as strings |
| Integer / decimal | `node.schema_distance.value` | String in Twig — cast for arithmetic |

**Geofield note — `.value` inconsistency:**
The article template uses `node.schema_geo.lat.value` for the node's own
geofield but `poi.schema_geo.lat` (no `.value`) for referenced geo_entity
geofields. The tourist-trip template was written with `.value` on entity geo
fields — this was a bug causing null map coordinates. The rule is:

- **Geo_entity geofield** → `entity.schema_geo.lat` / `.lon` (no `.value`)
- **Node's own geofield** → `node.schema_geo.lat.value` / `.lon.value`

---

## Content templates

### `node--article.html.twig` ✅

Handles both `article` and `trail_report` bundles. Trail-report-specific
fields (stats, geoshape, elevation) are conditionally included.

**Layout:** `@layouts/article/article.twig`

**Key traversals:**
```twig
{# GeoShape media → file #}
node.schema_geoshape.entity.field_media_file.entity.filename.value  {# IS a field #}
node.schema_geoshape.entity.field_media_file.entity.fileuri|file_url

{# Node's own geofield — center fallback, no marker #}
node.schema_geo.lat.value / .lon.value      {# .value used on node's geofield #}

{# Referenced geo_entity geofields — no .value #}
poi.schema_geo.lat / poi.schema_geo.lon

{# Geo entity labels — article template uses .value here #}
poi.label.value                             {# matches geo_entity label field pattern #}
dest.label.value

{# Category taxonomy #}
node.schema_category.entity.label.value          {# string #}
node.schema_category.entity.field_key.value {# IS a field #}
```

---

### `node--tourist-trip.html.twig` ✅

**Collection:** `@collections/trip/trip.twig`

**Key traversals:**
```twig
{# Hero image #}
node.schema_image.entity.field_media_image.entity.fileuri|file_url

{# Smart Date — [0] not .first #}
node.schema_trip_dates[0].value             {# Unix timestamp, start #}
node.schema_trip_dates[0].end_value         {# Unix timestamp, end #}

{# Itinerary loop builds destinations[], map_markers[], distance totals #}
node.schema_itinerary → article[]
  item.entity.schema_destination → geo_entity:destination[]
    dest.label.value                              {# string #}
    dest.schema_geo.lat / .lon              {# direct floats, no .value #}
    dest.schema_address.administrative_area
  item.entity.schema_poi → geo_entity:poi[]
    poi.schema_geo.lat / .lon               {# direct floats, no .value #}
    poi.label.value                               {# string #}
  item.entity.schema_date_published.value
  item.entity.schema_distance_*.value

{# Narrative heading from field_editorial #}
content.field_editorial|field_value|render  {# ?: null if empty #}
content.field_body|field_value|render
```

---

### `node--place.html.twig` ⚠️

Uses `node.schema_latitude.value` / `node.schema_longitude.value` — flat
legacy fields. Needs updating to `node.schema_geo.lat` / `.lon` once the
Place content type's geofield name is confirmed.

---

## Layout templates

### `layout--section-bare.html.twig`

```twig
{% if content %}
  <section{{ attributes }}>
    <div{{ region_attributes.content }}>
      {{ content.content }}
    </div>
  </section>
{% endif %}
```

### `layout--section-two-col.html.twig`

```twig
{{ attach_library('surface/content-aside') }}
<div{{ attributes.addClass('content-aside') }}>
  <div{{ region_attributes.main.addClass('content-aside__main') }}>
    {{ content.main }}
  </div>
  <aside{{ region_attributes.aside.addClass('content-aside__aside') }}>
    {{ content.aside }}
  </aside>
</div>
```

---

## Paragraph templates

All paragraph templates require `{{ attributes }}` on the outermost element
for Layout Paragraphs edit controls.

### `paragraph--place.html.twig` ⚠️

Uses `paragraph.schema_latitude.value` / `paragraph.schema_longitude.value`
— legacy flat fields. Update to `paragraph.schema_geo.lat` / `.lon`.

### `paragraph--trail-report.html.twig` ⚠️

Two bugs:
1. Image: `node.schema_image.entity.uri.value|image_style` — wrong traversal.
   Fix: `node.schema_image.entity.field_media_image.entity.fileuri|file_url`
2. References `node` context inside a paragraph template — `node` is not
   guaranteed to be in scope; use `paragraph` instead.

### `paragraph--trip.html.twig` ⚠️ LEGACY

References `paragraph.schema_destination` pointing at Place nodes — this
field no longer exists on the trip paragraph type. This template is
superseded by `node--tourist-trip.html.twig` + `@collections/trip/trip.twig`.

---

## Legacy template — delete this file

### `node--trip.html.twig` — DELETE

Two fatal bugs that will crash the page:
1. `node.schema_trip_dates.first` — `SecurityError` on Smart Date sandbox
2. `node.schema_destination` — field removed from tourist_trip bundle

Drupal uses the most specific template suggestion that exists. As long as
`node--trip.html.twig` is on disk, Drupal may use it over
`node--tourist-trip.html.twig` depending on the suggestion order. **Delete it.**

---

## Node template → collection map

| Template | Bundle(s) | Collection |
|---|---|---|
| `node--article.html.twig` | `article`, `trail_report` | `@layouts/article/article.twig` |
| `node--tourist-trip.html.twig` | `tourist_trip` | `@collections/trip/trip.twig` |

---

## Storybook vs Drupal template relationship

Storybook stories render the **collection** directly with static `.yml`
fixture data. The Drupal node template renders the same collection with data
assembled from live fields. They are design fixtures, not data mocks —
exact parity is not the goal.

When the story diverges from Drupal output in a way that matters, check:

1. Variable names match between node template and collection template.
2. No optional fields are hardcoded `null` in the node template when a real
   field source exists (e.g. `field_editorial` → `narrative_heading`).
3. The `.yml` fixture covers all the sections the collection can render so
   the story is a complete visual test.

---

## Twig cache

After any template edit, clear both the render cache and the Twig compiled
template cache. `drush cr` alone does not invalidate compiled Twig files and
stale templates are a common source of confusing rendering bugs.

```bash
lando drush cr
lando drush php-eval "\Drupal::service('twig')->invalidate();"
```
