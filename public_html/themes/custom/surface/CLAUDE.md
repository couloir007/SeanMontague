# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Surface Theme

## Project Overview

Personal site for seanmontague.com built on Drupal 10/11 with a custom
theme called **Surface**. The site covers Kingdom Trails mountain biking,
Burke Mountain skiing, permaculture/food forest, and Leaflet-based
interactive mapping. Sean is a web developer at the Smithsonian NMNH.
This is a personal outlet — not a portfolio or consulting site.

---

## Theme Architecture

```
surface/
├── source/
│   ├── assets/            # Static assets (SVGs, images)
│   ├── props/             # Design tokens (CSS custom properties)
│   │   └── nek.css        # NEK palette and font stacks
│   └── patterns/
│       ├── base/          # Global CSS, reset, typography, utilities,
│       │                  # animations, aspects, borders, brand, shadows
│       ├── elements/      # Atomic pieces: button, byline, date, eyebrow,
│       │                  # images, links, list, pager, quote, readtime,
│       │                  # skip-link, text, title, video
│       ├── components/    # Composed from elements: nav, hero, marquee,
│       │                  # map, card, places-card, about, contact,
│       │                  # footer, stats-bar, elevation-profile
│       │                  # Form: checkbox, checkbox-toggle, datetime,
│       │                  # details, fieldset, form-item, form-item-label,
│       │                  # input, search-form, user-login-form, user-pass-form
│       ├── collections/   # Composed from components: article,
│       │                  # article-header, article-map-section, map-section
│       ├── layouts/       # Drupal region wrappers: block, content-edit,
│       │                  # layout-container, main, media, region,
│       │                  # site-container, site-footer, site-header,
│       │                  # site-homepage, site-navigation
│       ├── pages/         # Full page Storybook demos
│       └── theme/         # Drupal admin UI overrides only
├── templates/             # Drupal .html.twig template suggestions
│   ├── content/           # node--*.html.twig
│   ├── layout/            # page.html.twig, layout--*.html.twig
│   ├── paragraphs/        # paragraph--*.html.twig
│   └── field/             # field--*.html.twig
├── surface.info.yml
├── surface.libraries.yml
├── surface.layouts.yml
└── surface.theme
```

### Twig Namespaces (vite.config.js + surface.info.yml)

```
@base        → source/patterns/base
@elements    → source/patterns/elements
@components  → source/patterns/components
@collections → source/patterns/collections
@layouts     → source/patterns/layouts
@pages       → source/patterns/pages
@theme       → source/patterns/theme
```

### Pattern Hierarchy Rules

- **Elements** include nothing — they are the smallest unit
- **Components** may include `@elements/` only
- **Collections** may include `@components/` and `@elements/`
- **Layouts** may include `@collections/`, `@components/`, `@elements/`
- **Pages** may include anything
- Never use `@components/surface/` — the old nested path is gone, all flat

---

## Design Tokens

All tokens defined in `source/props/nek.css` and `source/props/`:

```css
/* Palette */
--bg:       #f7f6f2   /* warm off-white page background */
--surface:  #ffffff   /* card/panel backgrounds */
--surface2: #f0ede6   /* slightly darker surface */
--border:   #e0dbd1
--muted:    #7a7568
--text:     #2c2a25
--bright:   #161410   /* near-black */
--forest:   #3a5a40   /* primary green — trails, links, accents */
--trail:    #7a3410   /* brown — mountain biking, skiing content */
--sky:      #4a7c9e   /* blue — maps content */
--stone:    #6b6560

/* Fonts */
--font-display-nek: 'Bebas Neue'          /* hero titles, stats values */
--font-serif-nek:   'Cormorant Garamond'  /* body text, article headings */
--font-mono-nek:    'DM Mono'             /* labels, nav, metadata, tags */
```

---

## Content Model (Schema.org Blueprints)

> **Field naming convention:** All Schema.org-mapped fields use the `schema_`
> prefix (e.g. `schema_geo`, `schema_place`, `schema_distance`). Core Drupal
> fields (`body`, `title`, `status`) and media fields (`field_image`,
> `field_media_image`) keep their default names.

### Mapping Sets (schemadotorg.mapping_sets.yml)

```yaml
required:
  label: Required
  types:
    - 'media:AudioObject'
    - 'media:ImageObject'
    - 'taxonomy_term:DefinedTerm'
    - 'node:Person'

website:
  label: 'Web Site'
  types:
    - 'node:WebSite'
    - 'node:WebPage'

place:
  label: Place
  types:
    - 'node:Place'

writing:
  label: Writing
  types:
    - 'node:BlogPosting'

trails:
  label: Trails
  types:
    - 'node:BlogPosting'

travel:
  label: Travel
  types:
    - 'node:TouristTrip'

events:
  label: Events
  types:
    - 'node:Event'
    - 'node:EventSeries'

audio:
  label: Audio
  types:
    - 'media:AudioObject'
    - 'node:PodcastEpisode'
    - 'node:PodcastSeries'

person:
  label: Person
  types:
    - 'node:Person'
```

### Content Types

| Bundle | Schema.org type | Purpose |
|---|---|---|
| `article` | `BlogPosting` | Writing, trail/ski reports, Drupal, permaculture posts |
| `trip` | `TouristTrip` | Multi-destination travel posts |
| `place` | `Place` | Locations referenced by other types |
| `event` | `Event` | Kingdom Trails events, group rides, clinics |
| `event_series` | `EventSeries` | Recurring events e.g. weekly group rides |
| `web_page` | `WebPage` | Static pages (About, Contact) |
| `web_site` | `WebSite` | Site-level structured data, authorship anchor |

### Taxonomy

| Vocabulary | Schema.org type | Terms |
|---|---|---|
| `category` | `DefinedTerm` | trails, garden, maps, tech |
| `activity_type` | `DefinedTerm` | Mountain Biking, Skiing, Permaculture… |

### Media

| Bundle | Schema.org type | Notes |
|---|---|---|
| `image` | `ImageObject` | General images |
| `audio` | `AudioObject` | ElevenLabs generated audio, podcast episodes |

### Audio / ElevenLabs Fields (media:AudioObject)

| Schema.org property | Drupal field | Notes |
|---|---|---|
| `schema:contentUrl` | file or external URL | Generated MP3 |
| `schema:duration` | duration field | Audio length |
| `schema:transcript` | text field | Source text fed to ElevenLabs |
| `schema:encodingFormat` | string | `audio/mpeg` |
| `schema:isPartOf` | entity ref → node | Post it belongs to |

Pattern: `AudioObject` media entity attached via `schema_audio` field.
Templates conditionally render an audio player when `schema_audio` is set.

### Key Field Relationships

```
article (BlogPosting)
  ├── title               → headline
  ├── body                → articleBody
  ├── schema_date_published → datePublished
  ├── schema_category     → DefinedTerm (category vocabulary)
  ├── schema_activity_type → DefinedTerm (activity_type vocabulary)
  ├── schema_trip         → TouristTrip (parent trip, optional)
  ├── schema_place        → Place (optional, geo-tagged writing)
  ├── schema_geo          → Geofield (lat/lon — map center fallback)
  ├── schema_geoshape     → file (GPX or GeoJSON upload)
  │                         On save: trail_mapper converts to GeoJSON
  │                         with Z values in feet, overwrites in place.
  │                         After save always contains .geojson.
  ├── schema_distance     → decimal (miles)
  ├── schema_elev_gain    → integer (feet)
  ├── schema_elev_loss    → integer (feet)
  ├── schema_elev_min     → integer (feet)
  ├── schema_elev_max     → integer (feet)
  ├── schema_difficulty   → list: Easy/Intermediate/Hard/Expert
  ├── schema_audio        → AudioObject (optional, ElevenLabs)
  └── field_image         → ImageObject

Stat fields (`schema_distance` through `schema_difficulty`) are optional —
only relevant for trail/ski articles. The stats bar renders from Twig when
`schema_distance` is set; stats are never passed through JS events.

trip (TouristTrip)
  ├── body                → description
  ├── schema_date         → startDate
  ├── field_image         → ImageObject
  ├── schema_audio        → AudioObject (optional)
  ├── schema_destination  → Place[] (multi-value)
  └── schema_itinerary    → article[] (ordered)

place (Place)
  ├── body                → description
  ├── field_image         → ImageObject
  ├── schema_geo          → Geofield (lat/lon)
  ├── schema_address      → Address field
  └── schema_tags         → DefinedTerm[]

event (Event)
  ├── body                → description
  ├── schema_date         → startDate
  ├── schema_end_date     → endDate
  ├── schema_place        → Place (location)
  └── schema_organizer    → Person
```

---

## Twig Rules

### Always use |field_value to strip field wrappers

```twig
{# CORRECT — strips <div class="field field--..."> wrapper #}
{{ content.field_body|field_value }}

{# For render arrays that need forcing #}
{{ content.field_body|field_value|render }}

{# WRONG — outputs full field wrapper markup #}
{{ content.field_body }}
```

### Always keep {{ attributes }} on paragraph outer elements

Layout Paragraphs edit controls and contextual links live on
`{{ attributes }}`. Never drop it from the outermost element of a
paragraph template.

```twig
{# CORRECT — LP edit controls preserved #}
<div{{ attributes }}>
  <div class="prose">{{ content.field_body|field_value }}</div>
</div>

{# WRONG — breaks LP edit controls #}
<div class="prose">{{ content.field_body|field_value }}</div>
```

### Always keep {{ region_attributes.NAME }} on layout region elements

```twig
{# CORRECT — LP "Add component" buttons work #}
<section{{ attributes }}>
  <div{{ region_attributes.content }}>
    {{ content.content }}
  </div>
</section>

{# WRONG — LP region buttons missing #}
<section>
  {{ content.content }}
</section>
```

### Multi-value fields

```twig
{% for item in content.schema_tags|field_value %}
  <span class="card__tag">{{ item }}</span>
{% endfor %}
```

### Image with specific image style via Twig Tweak

```twig
{{ drupal_field('field_image', 'node', node.id, 'image', {
  'image_style': 'card'
})|field_value }}
```

### Raw image URI

```twig
{% set uri = node.field_image.entity.field_media_image.entity.fileuri %}
{% set img_url = uri|file_url %}
```

### Geo coordinates from Geofield

```twig
{% set geo = node.schema_geo.value %}
{% set map_center = geo.lat ~ ',' ~ geo.lon %}
```

### Geo from referenced Place node

```twig
{% set place = node.schema_place.entity %}
{% set geo = place ? place.schema_geo.value : null %}
{% set map_center = geo ? geo.lat ~ ',' ~ geo.lon : '44.593,-71.918' %}
```

### Multi-destination map markers from Trip

```twig
{% set markers = [] %}
{% for item in node.schema_destination %}
  {% set place = item.entity %}
  {% if place and place.schema_geo.value %}
    {% set geo = place.schema_geo.value %}
    {% set markers = markers|merge([{
      'lat':   geo.lat,
      'lon':   geo.lon,
      'color': '#3a5a40',
      'label': '<strong>' ~ place.label ~ '</strong>',
    }]) %}
  {% endif %}
{% endfor %}
```

---

## Node Template Patterns

### node--article.html.twig

```twig
{% include '@collections/article/article.twig' with {
  'header': {
    'title':        node.label,
    'date':         node.schema_date_published.value|date('F j, Y'),
    'category':     node.schema_category.entity.label,
    'category_key': node.schema_category.entity.field_key.value,
  },
  'body': content.body|field_value|render,
} only %}
```

### node--trip.html.twig

```twig
{% set markers = [] %}
{% for item in node.schema_destination %}
  {% set place = item.entity %}
  {% if place and place.schema_geo.value %}
    {% set geo = place.schema_geo.value %}
    {% set markers = markers|merge([{
      'lat':   geo.lat,
      'lon':   geo.lon,
      'color': '#3a5a40',
      'label': '<strong>' ~ place.label ~ '</strong>',
    }]) %}
  {% endif %}
{% endfor %}

{% include '@components/map/map.twig' with {
  'map_id':      'trip-map-' ~ node.id,
  'center':      markers[0] ? markers[0].lat ~ ',' ~ markers[0].lon : '44.593,-71.918',
  'zoom':        8,
  'interactive': 'true',
  'markers':     markers,
} only %}

<div class="prose">
  {{ content.body|field_value }}
</div>
```

---

## node--article.html.twig Template Pattern

```twig
{# ── Geo sources ── #}
{% set has_geoshape = node.schema_geoshape.entity is not empty %}
{% set has_geo      = node.schema_geo.value is not empty %}
{% set place        = node.schema_place.entity %}
{% set has_place    = place and place.schema_geo.value %}
{% set has_map      = has_geoshape or has_geo or has_place %}

{# ── Map center fallback — overridden by fitBounds when geojson present ── #}
{% if has_geo %}
  {% set map_center = node.schema_geo.value.lat ~ ',' ~ node.schema_geo.value.lon %}
{% elseif has_place %}
  {% set map_center = place.schema_geo.value.lat ~ ',' ~ place.schema_geo.value.lon %}
{% else %}
  {% set map_center = '44.593,-71.918' %}
{% endif %}

{# ── Markers from schema_place only — schema_geo is center, not a marker ── #}
{% set map_markers = [] %}
{% if has_place %}
  {% set map_markers = map_markers|merge([{
    'lat':         place.schema_geo.value.lat,
    'lon':         place.schema_geo.value.lon,
    'color':       '#3a5a40',
    'label':       '<strong>' ~ place.label ~ '</strong>',
    'entity_type': 'node',
    'entity_id':   place.id,
  }]) %}
{% endif %}
```

Key rules:
- `schema_geo` provides map center only — never a marker
- `schema_place` provides markers with `entity_type`/`entity_id` for JS events
- `drupal_geo_rendered` is always `null` — map.js handles all rendering
- `geojson_url` = `node.schema_geoshape.entity.uri.value|file_url` when set
- Geofield access: `node.schema_geo.value.lat` and `.lon` (not `.lat` directly)

---

## Paragraph Template Pattern

```twig
{# templates/paragraphs/paragraph--[type].html.twig #}
{# {{ attributes }} is required — carries LP edit control metadata #}
<div{{ attributes }}>
  <div class="prose">
    {{ content.field_body|field_value }}
  </div>
</div>
```

---

## Layout Definitions

### Bare single-column section (surface.layouts.yml)

```yaml
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
```

### templates/layout/layout--section-bare.html.twig

```twig
{% if content %}
  <section{{ attributes }}>
    <div{{ region_attributes.content }}>
      {{ content.content }}
    </div>
  </section>
{% endif %}
```

### Bare two-column layout (article + sidebar)

```yaml
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

### templates/layout/layout--section-two-col.html.twig

```twig
{% if content %}
  <div{{ attributes.addClass('article-wrap') }}>
    <div{{ region_attributes.main.addClass('article__body') }}>
      {{ content.main }}
    </div>
    <aside{{ region_attributes.aside.addClass('article__sidebar') }}>
      {{ content.aside }}
    </aside>
  </div>
{% endif %}
```

---

## JavaScript Standards

All JS files in the theme must begin with:

```js
/* jshint esversion: 6 */
/* global Drupal */  // add L if Leaflet is used
```

- ES6+ throughout — `const`/`let`, arrow functions, template literals,
  optional chaining `?.`, nullish coalescing `??`, `async/await`
- No `var`
- IIFE pattern `(() => { ... })()` for all component JS
- Guard with `el._surfaceXInit = true` flag on the element — no `once()`
- Dual context: `Drupal.behaviors` in Drupal, `DOMContentLoaded` in Storybook

---

## Leaflet Map Component

The `map` component (`@components/map/map.twig`) is configured via data
attributes. `map.js` is ES6+, async/await IIFE — works in both Drupal
and Storybook.

**Tile config priority:**
1. `drupalSettings.trailMapper` (set by `trail_mapper` admin at `/admin/config/trail-mapper`)
2. `data-map-tiles` attribute on the element
3. `usgs-topo` default (USGS National Map — free, no API key)

**Available tile keys:** `usgs-topo`, `usgs-imagery`, `usgs-imagery-topo`,
`usgs-shaded`, `osm`

```twig
{% include '@components/map/map.twig' with {
  'map_id':      'unique-map-id',
  'center':      '44.593,-71.918',  {# fallback — overridden by fitBounds #}
  'zoom':        13,                {# fallback — overridden by fitBounds #}
  'interactive': 'true',            {# 'false' for hero background map #}
  'geojson_url': geojson_url,       {# URL → fetch → draw track + fitBounds all data #}
  'markers':     markers_array,     {# [{lat, lon, color, label, entity_type, entity_id}] #}
  'lines':       lines_array,       {# [{coords, color, weight, dash}] Storybook only #}
  'tiles':       'usgs-topo',       {# tile key, optional #}
} only %}
```

**Bounds behavior:**
- `geojson_url` present → fetch GeoJSON, add to map first, `fitBounds` track + markers
- No geojson, multiple markers → `fitBounds` markers
- No geojson, single/no markers → use `center`/`zoom` fallback

**After init — global state:**
- `window._surfaceMaps[map_id]` — Leaflet map instance
- `window._surfaceTracks[map_id]` — raw `[lon, lat, ele_ft]` coordinates
- `surface-map-ready` CustomEvent — `{ map_id, map, coords }` — track coords only; stat fields are **not** dispatched here
- `surface-map-marker-click` CustomEvent — `{ entity_type, entity_id, map_id, lat, lon }`

Stats (distance, elevation gain/loss/min/max, difficulty) come from Twig
manual fields only — rendered server-side, never passed through JS events.

**Elevation profile sync:**
`elevation-profile.js` checks `window._surfaceTracks[map_id]` first
(populated by `map.js` after fetch), then listens for `surface-map-ready`,
then falls back to `data-geojson-url` fetch, then `data-elev` inline JSON
(Storybook). One HTTP request shared between map and elevation profile.

Map containers require explicit height:

```css
/* Hero — position absolute, fills 100vh parent */
.hero__map-bg { position: absolute; inset: 0; }
.hero__map-bg .map { height: 100%; width: 100%; }

/* Map section — explicit container height */
.article-map-section__map-container { height: 440px; position: relative; }
.article-map-section__map-container .map { height: 100% !important; }
```

---

## surface.libraries.yml Pattern

Every pattern file gets its own library entry. The library name matches
the CSS/JS filename. Components attach their own library via
`{{ attach_library('surface/[name]') }}` in the Twig template.

```yaml
map:
  css:
    component:
      dist/css/map.css: {}
  js:
    dist/js/map.js: {}

article:
  css:
    component:
      dist/css/article.css: {}
  dependencies:
    - surface/article-map-section

article-map-section:
  css:
    component:
      dist/css/article-map-section.css: {}
  dependencies:
    - surface/map
    - surface/elevation-profile
```

---

## Common Commands

```bash
# From theme directory: public_html/themes/custom/surface/
lando npm run build           # full production build (lint + format + stylelint + vite)
lando npm run watch           # dev mode: Vite + Storybook on localhost:6007
lando npm run lint:fix        # Biome JS/TS auto-fix
lando npm run lint:check      # Biome check (no write)
lando npm run stylelint:fix   # CSS auto-fix
lando npm run stylelint:check # CSS check (no write)
lando npm run format:write    # Biome format auto-fix
lando npm run storybook:build # build static Storybook to /storybook

# Schema.org content type creation — always in this dependency order
lando drush schemadotorg:create-type taxonomy_term:DefinedTerm
lando drush schemadotorg:create-type media:ImageObject
lando drush schemadotorg:create-type media:AudioObject
lando drush schemadotorg:create-type node:Person
lando drush schemadotorg:create-type node:Place
lando drush schemadotorg:create-type node:BlogPosting
lando drush schemadotorg:create-type node:TouristTrip
lando drush schemadotorg:create-type node:Event

# Clear cache after template changes
lando drush cr
```

---

## Accessibility — Keyboard Navigation

The theme has these foundations already in place:
- `skip-link.twig` included in `html.html.twig` — links to `#main-content`
- `<main id="main-content" tabindex="-1">` in `site-container.twig`
- `focus.css` — `*:focus-visible` outline using `currentColor`

### Rules for every generated template

**Always include ARIA landmarks on structural elements:**

```twig
<header role="banner">        {# site header — one per page #}
<nav role="navigation">       {# always add aria-label when multiple navs exist #}
<main id="main-content" tabindex="-1" role="main">
<aside role="complementary">  {# sidebar #}
<footer role="contentinfo">   {# site footer #}
```

**Label navigation when multiple nav elements exist on a page:**

```twig
<nav role="navigation" aria-label="{{ 'Main navigation'|t }}">
<nav role="navigation" aria-label="{{ 'Breadcrumb'|t }}">
<nav role="navigation" aria-label="{{ 'Article navigation'|t }}">
```

**Interactive elements must be keyboard reachable:**

```twig
{# Links always use <a href> — never divs or spans with onclick #}
<a href="{{ url }}">{{ label }}</a>

{# Buttons that don't navigate use <button> not <a> #}
<button type="button" class="nav__toggle">{{ 'Menu'|t }}</button>

{# Never remove focus outline — focus.css handles styling via focus-visible #}
{# Never add outline: none or outline: 0 anywhere in component CSS #}
```

**Skip link is already in html.html.twig — do not add it again:**

```twig
{# Already rendered via html.html.twig: #}
{% include '@elements/skip-link/skip-link.twig' %}

{# Target must exist on every page: #}
<main id="main-content" tabindex="-1">
```

**Tab order — follow DOM order, never use positive tabindex:**

```twig
{# CORRECT — natural DOM order #}
<div class="hero__content">
  <h1>...</h1>
  <p>...</p>
  <a href="#">CTA</a>
</div>

{# WRONG — positive tabindex breaks natural tab order #}
<a href="#" tabindex="3">CTA</a>

{# Only tabindex="-1" is permitted — for programmatic focus targets only #}
<main id="main-content" tabindex="-1">
```

**Images — always include alt text:**

```twig
{# Decorative — empty alt #}
<img src="{{ url }}" alt="">

{# Informative — descriptive alt from media entity #}
{% set alt = node.field_image.entity.field_media_image.alt %}
<img src="{{ img_url }}" alt="{{ alt }}">
```

**Map component — non-interactive maps must not receive keyboard focus:**

```twig
{# Hero background — not keyboard navigable #}
{% include '@components/map/map.twig' with {
  'interactive': 'false',
} only %}

{# Interactive maps — keyboard zoom via Leaflet defaults #}
{% include '@components/map/map.twig' with {
  'interactive': 'true',
} only %}
```

**Form inputs — always associate labels:**

```twig
<label for="contact-email">{{ 'Email'|t }}</label>
<input id="contact-email" type="email" name="email">
{# Never rely on placeholder as the only label #}
```

### WCAG Resources

- [Axe Rule: Color Contrast](https://dequeuniversity.com/rules/axe/4.11/color-contrast?application=axeAPI)
- [Axe Rule: Link in Text Block](https://dequeuniversity.com/rules/axe/4.11/link-in-text-block?application=axeAPI)

---

## What NOT to Do

- Do not add `@components/surface/` paths — old nested structure, removed
- Do not drop `{{ attributes }}` from paragraph templates — breaks LP
- Do not drop `{{ region_attributes.NAME }}` from layout regions — breaks LP
- Do not output `{{ content.field_name }}` without `|field_value` — outputs wrapper divs
- Do not hardcode map heights in component CSS without the `!important`
  override rule for the `.map` child
- Do not use `once()` in JS — use `el._surfaceXInit` guard flag instead
- Do not use `var` in JS — use `const`/`let` (ES6+)
- Do not omit `/* jshint esversion: 6 */` from JS files
- Do not use Drupal Leaflet module formatter on article pages —
  `map.js` handles all Leaflet rendering
- Do not pass `drupal_geo_rendered` from `schema_geo` — set it to `null`;
  map.js renders maps, Drupal Leaflet is not used for article maps
- Do not create content types before their dependencies (Place before
  Article, DefinedTerm before everything)

---

## Storybook

Storybook runs at `http://localhost:6007` via `lando npm run watch`. It uses:
- `@storybook/html-vite` framework with Twig template rendering
- Addons: `a11y` (accessibility panel), `themes`, `docs`, `links`
- Custom viewports and a Drupal behaviors shim (`drupal.js`, `once.js`)
- Google Fonts, FontAwesome, and Leaflet loaded in `preview-head.html`

Each pattern's `.stories.js` file drives Storybook. Use `lando npm run storybook:build` to generate a static build in `/storybook`.

See root `CLAUDE.md` for Drupal commands, git workflow, config management, and environment setup.
