# Surface Theme — Layout Paragraphs Implementation Guide

## 1. Paragraph Template Override

The goal is raw field values with no Drupal wrapper markup.
`|field_value` from Twig Field Value gives you the rendered value of
the first item (or all items) without `<div class="field field--...">` soup.

### Formatted text field

```twig
{# templates/paragraphs/paragraph--blog-text.html.twig #}
{{ content.field_body|field_value }}
```

`|field_value` on a `text_with_summary` field returns the processed HTML
string directly — no `<div class="field__item">` wrapper.
The `surface_preprocess_field()` hook in `includes/html.theme` already
adds `class="prose"` to rich text fields in the full field render.
When you use `|field_value` you bypass that wrapper entirely, so apply
the prose class on your own element:

```twig
<div class="prose">
  {{ content.field_body|field_value }}
</div>
```

### Image field

```twig
{# Renders the image through the configured formatter, strips field wrapper #}
{{ content.field_image|field_value }}
```

If you need the raw URL only (e.g. to pass to a Twig include as a variable):

```twig
{% set img_url = content.field_image|field_value|render|trim %}
```

Or use Twig Tweak to get the file URI and transform it:

```twig
{% set uri = node.field_image.entity.field_media_image.entity.fileuri %}
{% set img_url = uri|file_url %}
<img src="{{ img_url }}" alt="{{ node.field_image.entity.field_media_image.alt }}">
```

### Multi-value field

`|field_value` returns the first item by default. For all items:

```twig
{% for item in content.field_tags|field_value %}
  <span class="surface-card__tag">{{ item }}</span>
{% endfor %}
```

---

## 2. Bare-Bones Custom Layout

### surface.layouts.yml entry

Add this to `surface.layouts.yml` alongside the existing layouts:

```yaml
# Bare section — no wrapper divs, just a <section> and region slots.
section_bare:
  label: 'Section (bare)'
  path: templates/layout
  template: layout--section-bare
  library: surface/layout
  category: 'Surface'
  default_region: content
  icon_map:
    - [content]
  regions:
    content:
      label: Content

# Two-column bare — for article body + sidebar pattern.
section_two_col:
  label: 'Section Two Column (bare)'
  path: templates/layout
  template: layout--section-two-col
  library: surface/layout
  category: 'Surface'
  default_region: main
  icon_map:
    - [main, aside]
  regions:
    main:
      label: Main
    aside:
      label: Aside
```

### templates/layout/layout--section-bare.html.twig

```twig
{#
 * Bare single-column section layout.
 * No wrapper divs — just the section element and the region slot.
 * Layout Paragraphs edit controls are preserved via {{ attributes }}.
 #}
{% if content %}
  <section{{ attributes }}>
    {{ content.content }}
  </section>
{% endif %}
```

### templates/layout/layout--section-two-col.html.twig

```twig
{#
 * Bare two-column layout — article body + sidebar.
 * Matches surface-article-wrap CSS grid.
 #}
{% if content %}
  <div{{ attributes.addClass('surface-article-wrap') }}>
    <div{{ region_attributes.main.addClass('surface-article__body') }}>
      {{ content.main }}
    </div>
    <aside{{ region_attributes.aside.addClass('surface-article__sidebar') }}>
      {{ content.aside }}
    </aside>
  </div>
{% endif %}
```

Key points:
- `{{ attributes }}` on the outermost element is **required** — Layout Paragraphs
  injects `data-layout-paragraphs-*` attributes here for its edit controls.
- `{{ region_attributes.REGION_NAME }}` is required on each region element —
  this is where LP injects the "Add component" buttons.
- No extra wrapper divs means your CSS classes apply directly.

---

## 3. Preserving Layout Paragraphs Edit Controls

This is the most common source of confusion. `|field_value` strips the
field wrapper but **does not strip** the LP edit controls — those live on
the paragraph entity wrapper and layout attributes, not the field wrapper.

### What LP needs to function

Layout Paragraphs requires three things to be present in the DOM:

1. `{{ attributes }}` on the layout's outermost element (carries
   `data-layout-paragraphs-layout` and the entity UUID)
2. `{{ region_attributes.REGION }}` on each region element (carries
   `data-layout-paragraphs-region`)
3. The paragraph entity wrapper — output by the paragraph template itself

The paragraph template's `{{ attributes }}` carries:
- `data-quickedit-entity-id` (Quick Edit)
- `data-layout-paragraphs-component` (LP component identifier)

### Safe paragraph template pattern

```twig
{# templates/paragraphs/paragraph--blog-text.html.twig #}
{#
 * {{ attributes }} on the outer element preserves LP edit controls.
 * |field_value strips the field wrapper without touching paragraph metadata.
 #}
<div{{ attributes }}>
  <div class="prose">
    {{ content.field_body|field_value }}
  </div>
</div>
```

`{{ attributes }}` here renders the paragraph's own attributes including
all `data-layout-paragraphs-*` and contextual link metadata. Never drop it.

### What you can safely strip

```twig
{# SAFE — strips field wrapper, keeps paragraph wrapper #}
{{ content.field_image|field_value }}

{# SAFE — renders field with custom formatter, strips wrapper #}
{{ drupal_field('field_image', 'node', node.id, 'image_style', {
  'image_style': 'hero'
})|field_value }}

{# UNSAFE — drops {{ attributes }}, breaks LP edit controls #}
{{ content.field_body|field_value }}  {# fine on its own... #}
{# ...but only if you still have {{ attributes }} somewhere above it #}
```

### When |field_value causes empty output

If `|field_value` returns nothing, the field is likely rendered as a
render array, not a string. Force it:

```twig
{{ content.field_body|field_value|render }}
```

---

## 4. Advanced Twig Tweak + field_value

`drupal_field()` renders a field with a specific formatter.
Chain `|field_value` after it to strip the field wrapper from that output.

### Image with a specific image style

```twig
{# Render field_image using the 'card' image style, strip wrapper #}
{{ drupal_field('field_image', 'node', node.id, 'image', {
  'image_style': 'card'
})|field_value }}
```

### Geo field rendered as a Leaflet map

Your theme already has `templates/field/field--field-geo.html.twig` which
outputs `{{ items[0].content }}` — that's already as clean as it gets.
But if you need the raw coordinates to pass to `surface-map.twig`:

```twig
{# Get lat/lon from geofield to pass to surface-map include #}
{% set geo = node.field_geo.value %}
{% set map_center = geo.lat ~ ',' ~ geo.lon %}

{% include '@components/map/surface-map.twig' with {
  'map_id':      'node-map-' ~ node.id,
  'center':      map_center,
  'zoom':        13,
  'interactive': 'true',
} only %}
```

### Referenced Place node — pulling geo for a TrailReport

```twig
{# node--trail-report.html.twig #}
{# Pull geo from referenced Place node for the map center #}
{% set place = node.field_place.entity %}
{% set geo = place ? place.field_geo.value : null %}
{% set map_center = geo ? geo.lat ~ ',' ~ geo.lon : '44.593,-71.918' %}

{% include '@collections/article/surface-article.twig' with {
  'header': {
    'title':      node.label,
    'date':       node.field_date_published.value|date('F j, Y'),
    'category':   node.field_category.entity.label,
    'difficulty': node.field_difficulty.value,
    'stats': [
      { 'label': 'Distance',  'value': node.field_distance.value,   'unit': 'miles' },
      { 'label': 'Elevation', 'value': node.field_elev_gain.value,  'unit': 'ft gain' },
      { 'label': 'Min Elev',  'value': node.field_elev_min.value,   'unit': 'ft' },
      { 'label': 'Max Elev',  'value': node.field_elev_max.value,   'unit': 'ft' },
    ],
  },
  'map_id':     'trail-map-' ~ node.id,
  'map_center': map_center,
  'map_zoom':   13,
  'body':       content.body|field_value|render,
} only %}
```

### Trip node — multi-destination map

```twig
{# node--trip.html.twig #}
{# Build markers array from all referenced Place nodes #}
{% set markers = [] %}
{% for item in node.field_destination %}
  {% set place = item.entity %}
  {% if place and place.field_geo.value %}
    {% set geo = place.field_geo.value %}
    {% set markers = markers|merge([{
      'lat':   geo.lat,
      'lon':   geo.lon,
      'color': '#3a5a40',
      'label': '<strong>' ~ place.label ~ '</strong>',
    }]) %}
  {% endif %}
{% endfor %}

{% include '@components/map/surface-map.twig' with {
  'map_id':      'trip-map-' ~ node.id,
  'center':      markers[0].lat ~ ',' ~ markers[0].lon,
  'zoom':        9,
  'interactive': 'true',
  'markers':     markers,
} only %}

<div class="prose">
  {{ content.body|field_value }}
</div>
```

---

## 5. Summary — Rules of Thumb

| Situation | Solution |
|---|---|
| Strip field wrapper, keep value | `\|field_value` |
| Force render of render array | `\|field_value\|render` |
| Custom image formatter | `drupal_field()\|field_value` |
| Raw geo coordinates | `node.field_geo.value.lat` / `.lon` |
| Preserve LP edit controls | Always keep `{{ attributes }}` on paragraph outer element |
| Preserve LP region buttons | Always keep `{{ region_attributes.NAME }}` on region elements |
| Multiple field values | `for item in content.field_NAME\|field_value` |
