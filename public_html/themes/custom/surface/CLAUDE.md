# Surface Theme — Claude Code Project Memory

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
│   └── patterns/
│       ├── base/          # Global CSS, reset, typography, utilities
│       ├── elements/      # Atomic pieces: button, byline, date, eyebrow,
│       │                  # images, links, list, pager, quote, readtime,
│       │                  # skip-link, text, title, video
│       ├── components/    # Composed from elements: nav, hero, marquee,
│       │                  # map, card, places-card, about, contact,
│       │                  # footer, stats-bar, elevation-profile
│       ├── collections/   # Composed from components: article,
│       │                  # article-header, article-map-section, map-section
│       ├── layouts/       # Drupal region wrappers: block, site-container,
│       │                  # site-header, site-footer, site-navigation,
│       │                  # site-homepage
│       ├── pages/         # Full page Storybook demos
│       └── theme/         # Drupal admin UI overrides only
├── templates/             # Drupal .html.twig template suggestions
│   ├── content/           # node--*.html.twig
│   ├── layout/            # page.html.twig, layout--*.html.twig
│   ├── paragraphs/        # paragraph--*.html.twig
│   └── field/             # field--*.html.twig
├── props/                 # Design tokens (CSS custom properties)
│   └── nek.css            # NEK palette and font stacks
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

All tokens defined in `props/nek.css` and `props/`:

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
| `article` | `BlogPosting` | Writing, Drupal, permaculture posts |
| `trail_report` | `BlogPosting` | Ride/ski reports with stats + map |
| `trip` | `TouristTrip` | Multi-destination travel posts |
| `place` | `Place` | Locations referenced by other types |
| `event` | `Event` | Kingdom Trails events, group rides, clinics |
| `event_series` | `EventSeries` | Recurring events e.g. weekly group rides |
| `web_page` | `WebPage` | Static pages (About, Contact) |
| `web_site` | `WebSite` | Site-level structured data, authorship anchor |

**Why two BlogPosting bundles (`article` + `trail_report`):**
Navigation is handled by menus, not path patterns. Bundles are split purely
for editorial UX — trail reports need stat fields that would clutter the
writing post edit form. Schema.org output is identical (`@type: BlogPosting`).

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
  ├── body                → articleBody
  ├── schema_category     → DefinedTerm (trails/garden/maps/tech)
  ├── schema_date         → datePublished
  ├── field_image         → ImageObject
  ├── schema_audio        → AudioObject (optional, ElevenLabs)
  └── schema_place        → Place (optional, geo-tagged writing)

trail_report (BlogPosting)
  ├── body                → articleBody
  ├── schema_category     → DefinedTerm
  ├── schema_date         → datePublished
  ├── field_image         → ImageObject
  ├── schema_audio        → AudioObject (optional)
  ├── schema_place        → Place (carries geo for map center)
  ├── schema_trip         → TouristTrip (optional, schema:isPartOf)
  ├── schema_distance     → decimal
  ├── schema_elev_gain    → integer
  ├── schema_elev_loss    → integer
  ├── schema_elev_min     → integer
  ├── schema_elev_max     → integer
  ├── schema_difficulty   → list: Easy/Intermediate/Hard/Expert
  └── schema_gpx          → file

trip (TouristTrip)
  ├── body                → description
  ├── schema_date         → startDate
  ├── field_image         → ImageObject
  ├── schema_audio        → AudioObject (optional)
  ├── schema_destination  → Place[] (multi-value)
  └── schema_itinerary    → [trail_report|article][] (ordered)

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
  <span class="surface-card__tag">{{ item }}</span>
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

### node--trail-report.html.twig

```twig
{% set place = node.schema_place.entity %}
{% set geo = place ? place.schema_geo.value : null %}
{% set map_center = geo ? geo.lat ~ ',' ~ geo.lon : '44.593,-71.918' %}

{% include '@collections/article/surface-article.twig' with {
  'header': {
    'title':      node.label,
    'date':       node.schema_date_published.value|date('F j, Y'),
    'category':   node.schema_category.entity.label,
    'category_key': node.schema_category.entity.field_key.value,
    'difficulty': node.schema_difficulty.value,
    'stats': [
      { 'label': 'Distance',  'value': node.schema_distance.value,  'unit': 'miles' },
      { 'label': 'Elevation', 'value': node.schema_elev_gain.value, 'unit': 'ft gain' },
      { 'label': 'Loss',      'value': node.schema_elev_loss.value, 'unit': 'ft loss' },
      { 'label': 'Min Elev',  'value': node.schema_elev_min.value,  'unit': 'ft' },
      { 'label': 'Max Elev',  'value': node.schema_elev_max.value,  'unit': 'ft' },
    ],
  },
  'map_id':     'trail-map-' ~ node.id,
  'map_center': map_center,
  'map_zoom':   13,
  'body':       content.body|field_value|render,
} only %}
```

### node--article.html.twig

```twig
{% include '@collections/article/surface-article.twig' with {
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

{% include '@components/map/surface-map.twig' with {
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

---

## Leaflet Map Component

The `surface-map` component is configured entirely via data attributes.
All JS init is a plain IIFE — works in both Drupal and Storybook.
Tile provider: USGS National Map (free, no API key, feet).

```twig
{% include '@components/map/surface-map.twig' with {
  'map_id':      'unique-map-id',
  'center':      '44.593,-71.918',
  'zoom':        12,
  'interactive': 'true',          {# 'false' for hero background map #}
  'markers':     markers_array,   {# [{lat, lon, color, label}] #}
  'lines':       lines_array,     {# [{coords, color, weight, dash}] #}
} only %}
```

Map containers require explicit height. The `.surface-map` div fills 100%
of its parent — the parent must have a fixed height:

```css
/* Hero — position absolute, fills 100vh parent */
.surface-hero__map-bg { position: absolute; inset: 0; }
.surface-hero__map-bg .surface-map { height: 100%; width: 100%; }

/* Map section — explicit container height */
.surface-map-section__map-container { height: 440px; position: relative; }
.surface-map-section__map-container .surface-map { height: 100% !important; }
```

---

## surface.libraries.yml Pattern

Every pattern file gets its own library entry. The library name matches
the CSS/JS filename. Components attach their own library via
`{{ attach_library('surface/surface-[name]') }}` in the Twig template.

```yaml
surface-map:
  css:
    component:
      dist/css/surface-map.css: {}
  js:
    dist/js/surface-map.js: {}

surface-article:
  css:
    component:
      dist/css/surface-article.css: {}
  dependencies:
    - surface/surface-article-map-section

surface-article-map-section:
  css:
    component:
      dist/css/surface-article-map-section.css: {}
  dependencies:
    - surface/surface-map
    - surface/surface-elevation-profile
```

---

## Common Commands

```bash
# Build Storybook assets
npm run build

# Watch mode (Storybook + Vite)
npm run watch

# Drush — Schema.org content type creation order
drush schemadotorg:create-type taxonomy_term:DefinedTerm
drush schemadotorg:create-type media:ImageObject
drush schemadotorg:create-type node:Place
drush schemadotorg:create-type node:BlogPosting
drush schemadotorg:create-type node:TouristTrip

# Clear cache after template changes
drush cr
```

---

## What NOT to Do

- Do not add `@components/surface/` paths — old nested structure, removed
- Do not drop `{{ attributes }}` from paragraph templates — breaks LP
- Do not drop `{{ region_attributes.NAME }}` from layout regions — breaks LP
- Do not output `{{ content.field_name }}` without `|field_value` — outputs wrapper divs
- Do not hardcode map heights in component CSS without the `!important`
  override rule for the `.surface-map` child
- Do not use `once()` in map/elevation JS — use the plain IIFE dual-context
  pattern that works in both Drupal and Storybook
- Do not create content types before their dependencies (Place before
  TrailReport, DefinedTerm before everything)

---

## Local Development Environment

- **Local:** Lando with Pantheon recipe
- **Hosting:** Pantheon
- **Workflow:** Git-based, Terminus for database/file syncing

### Command Rules — Always Use Lando Prefixes

Never run `drush`, `composer`, `npm`, or `terminus` directly on the host.
Always prefix with `lando`:

```bash
# Drupal
lando drush cr                    # clear cache
lando drush cim                   # config import
lando drush csex                  # config export
lando drush schemadotorg:create-type node:Place

# Composer
lando composer require drupal/schemadotorg
lando composer install

# Frontend (from theme directory)
lando npm run build
lando npm run watch

# Pantheon
lando terminus env:deploy my-site.dev
lando terminus drush my-site.dev -- cr
```

### File Structure

- **Web root:** `/public_html`
- **Custom theme:** `/public_html/themes/custom/surface`
- **Custom modules:** `/public_html/modules/custom`
- **Config sync:** `/config/sync`

### Settings

- Do not edit `settings.php` directly
- Local overrides go in `settings.lando.php`
- Lando/Pantheon recipe handles DB credentials automatically

### Git Workflow

- Atomic commits referencing ticket numbers
- Do not commit: `public_html/core`, `public_html/modules/contrib`,
  `public_html/themes/contrib`
- Config changes (`/config/sync`) should be committed with the feature
  that requires them — never separately unless a config-only change

### Config Management

Always export after UI changes before committing:

```bash
lando drush csex
git add config/sync
git commit -m "TICKET-123: Export config for [feature]"
```

Always import on environment setup or after pulling:

```bash
lando drush cim
lando drush cr
```
