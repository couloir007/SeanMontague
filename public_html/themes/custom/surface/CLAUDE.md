# Surface Theme — Claude Code Project Memory

## Project Overview

Personal site for **Sean Montague** (seanmontague.com) — Kingdom Trails
mountain biking, Burke Mountain skiing, permaculture/food forest, and
Leaflet-based interactive mapping. Built on **Drupal 10/11**, hosted on
**Pantheon**, local dev via **Lando**. Custom theme: **Surface**.

This is a personal outlet — not a portfolio or consulting site.

> **Read `STORYBOOK.md` before touching anything in `source/`.** It covers JS
> dual-context rules, Twig restrictions, YAML fixture indentation, and the
> common ways Storybook breaks. This file is the architecture/Drupal-integration
> reference; `source/CLAUDE.md` is the design-system reference.

---

## Theme Architecture

```
surface/
├── source/                    # Storybook design system — see source/CLAUDE.md
│   ├── assets/
│   │   ├── fonts/             # Self-hosted woff2/ttf (Bebas Neue, Cormorant, DM Mono)
│   │   └── images/            # SVGs
│   ├── props/                 # Design tokens — base set + nek.css overrides
│   └── patterns/
│       ├── base/              # Reset, global, typography, utilities
│       ├── elements/          # Atomic: button, byline, date, eyebrow, title…
│       ├── components/        # Composed from elements: map, hero, card, nav…
│       ├── collections/       # Composed from components: article, trip, map-section…
│       ├── layouts/           # Drupal region wrappers: site-header, site-container…
│       ├── pages/             # Full-page Storybook demos
│       └── theme/             # Drupal admin UI overrides only
├── templates/                 # Drupal twig templates
│   ├── content/               # node--*.html.twig
│   ├── layout/                # page.html.twig, layout--*.html.twig
│   ├── paragraphs/            # paragraph--*.html.twig
│   └── field/                 # field--*.html.twig
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

Namespaces are **flat**. The pattern is always `@{level}/{name}/{name}.twig`.
Never add a middle segment — `@components/surface/map/map.twig` is the old
nested path and is gone.

### Pattern Hierarchy Rules

| Layer | May include |
|---|---|
| Elements | Nothing |
| Components | `@elements/` only |
| Collections | `@components/` + `@elements/` |
| Layouts | `@collections/`, `@components/`, `@elements/` |
| Pages | Anything |

Styling follows the same boundary: a layer styles its own selectors and the
**modifiers** of what it includes — never the bare selectors of a deeper layer.

### Class naming — flat BEM, no theme prefix

Components use plain BEM blocks: `.map`, `.hero`, `.article__body`,
`.nav__links`. There is **no** `surface-` class prefix. (The library *names*
in `surface.libraries.yml` are `surface/map`, `surface/hero` etc. — that's the
Drupal library namespace, not a CSS class prefix. Don't confuse the two.)

---

## Design Tokens

Tokens come in two layers, both under `source/props/` (imported via
`props/index.css`):

1. **Base design system** — `sizes.css`, `shadows.css`, `borders.css`,
   `aspects.css`, `easing.css`, `zindex.css`, `animations.css`, `media.css`,
   `colors.css`, `fonts.css`. Generic primitives (`--size-8`, `--shadow-3`,
   `--font-size-7`, `--font-weight-700`).
2. **NEK overrides** — `nek.css`, imported **last** so it wins. This is the
   site's actual palette and font stacks.

```css
/* NEK palette (props/nek.css) */
--bg:          #f7f6f2   /* warm off-white page background */
--surface:     #ffffff   /* card/panel backgrounds */
--surface2:    #f0ede6   /* slightly darker surface */
--border:      #e0dbd1
--border-dark: #c8c2b8   /* darker border for hover states */
--muted:       #736e62
--text:        #2c2a25
--bright:      #161410   /* near-black */
--forest:      #3a5a40   /* primary green — trails, links, accents */
--trail:       #7a3410   /* brown — mountain biking, skiing content */
--sky:         #4a7c9e   /* blue — maps content */
--stone:       #6b6560
--amber:       #a05a00   /* difficulty badge text */
--amber-bg:    #fff3e0   /* difficulty badge background */
--amber-border:#f0d090   /* difficulty badge border */

/* NEK font stacks */
--font-mono-nek:    'DM Mono', monospace            /* labels, nav, metadata, tags */
--font-serif-nek:   'Cormorant Garamond', serif     /* body text, article headings */
--font-display-nek: 'Bebas Neue', sans-serif        /* hero titles, stats values, eyebrows */

/* Layout widths */
--content-width: 720px   /* article body column */
--wide-width:   1100px   /* article/header container max */
```

**Always use tokens, never raw hex.** `nek.css` also remaps the generic
`--font-primary` / `--font-secondary` to the NEK serif/display stacks, so base
typography picks them up automatically.

---

## Fonts — self-hosted via @font-face

Defined in `source/patterns/base/fonts/fonts.css`. **Not** base64-inlined.

- **Bebas Neue** — display face. Single weight; all-caps glyphs.
- **Cormorant Garamond** — serif, **variable font** (`font-weight: 100 900`
  in `@font-face`, single file per style). Body + article headings.
- **DM Mono** — mono. Multiple weights (Light 300, Regular 400, Medium 500)
  plus italics. Labels, nav, metadata, tags.

Vite copies fonts from `source/assets/fonts/` to `dist/fonts/` at build. The
`@font-face` `src` paths are `../fonts/NAME.woff2` — relative to the compiled
CSS in `dist/css/`. **Correct path is `../fonts/`, not `../assets/fonts/`** —
a recurring Vite output-path fix.

All faces use `font-display: swap`.

---

## Content Model (Schema.org Blueprints)

All content types use **Schema.org Blueprints**. Schema-mapped fields use the
`schema_` prefix; editorial/functional fields use `field_`. **Read
`SCHEMADOTORG.md` (repo root) before creating any field or content type** —
especially the shared-storage rule and the Blueprints-first workflow.

> **Configuration managed via `lando drush cex` / `cim`. Never create fields
> programmatically (`hook_install`) — it conflicts with config-managed fields.**

### Single article bundle

All written content uses one `article` (BlogPosting) bundle. `schema_category`
and `schema_activity_type` taxonomy drive display and navigation. There is **no**
separate `trail_report` bundle.

### Content types & geo entities

| Type | Bundle | Schema.org | Purpose |
|---|---|---|---|
| `node` | `article` | `BlogPosting` | All trail reports, writing, Drupal, permaculture |
| `node` | `trip` | `TouristTrip` | Multi-destination travel posts |
| `node` | `place` | `Place` | Content hub landing pages (Kingdom Trails, Burke) |
| `geo_entity` | `poi` | `TouristAttraction` | Map markers — attractions, features |
| `geo_entity` | `destination` | `Place` | Trip stop anchors (multi-value on trip) |

`geo_entity:destination` ← TouristTrip `schema_destination`.
`geo_entity:poi` ← article `schema_poi`.
`node:place` ← article `schema_place` (content hub).

Full field tables live in the repo-root `CLAUDE.md` Content Model section.
The geo-field access rules below are the ones that bite in templates.

### Geo field access — CRITICAL

Two different geo patterns. Never mix them up.

**Article** — `schema_geo` is a **Geofield**:
```twig
{% set has_geo = not node.schema_geo.isEmpty() %}
{% set map_center = node.schema_geo.lat.value ~ ',' ~ node.schema_geo.lon.value %}
```
`.lat` / `.lon` return `FloatData` TypedData objects, **not** raw floats.
Always append `.value` before any string context (`~`, `json_encode`).

**Place** — separate **decimal** fields, no Geofield:
```twig
node.schema_latitude.value
node.schema_longitude.value
```

**Place geo read from a referenced Place on an article:**
```twig
{% set place = node.schema_place.entity %}
{% set has_place = place and place.schema_latitude.value is not empty %}
{% set map_center = place.schema_latitude.value ~ ',' ~ place.schema_longitude.value %}
```
Common mistake: `place.schema_geo.value.lat` — Place has no `schema_geo`, so
the guard silently evaluates false.

**geo_entity (poi/destination):**
```twig
'lat': poi.schema_geo.lat.value,
'lon': poi.schema_geo.lon.value,
{# geo_entity uses "label" in entity keys → .label is a FieldItemList, not a string #}
'label': '<strong>' ~ poi.label.value ~ '</strong>',
```

**schema_geoshape file entity** (a `data_download` media reference):
```twig
{% set geo_media = node.schema_geoshape.entity %}
{% set geo_file  = geo_media ? geo_media.field_media_file.entity : null %}
{% set is_gpx    = geo_file and geo_file.filename.value matches '/\\.gpx$/i' %}
```
`.filename` is a FieldItemList — always `.value` before `matches`/string ops.

---

## Twig Rules (Drupal templates under `templates/`)

### Use `|field_value` to strip field wrappers

```twig
{# CORRECT — strips the <div class="field field--..."> wrapper #}
{{ content.body|field_value }}
{{ content.body|field_value|render }}   {# force render arrays #}

{# WRONG — outputs full field wrapper markup #}
{{ content.body }}
```
`|field_value` is a **Drupal-only** filter. It works in `templates/` but
**crashes Storybook** — never use it inside `source/patterns/`. Pattern
templates render purely from passed variables.

### Keep `{{ attributes }}` on paragraph outer elements

Layout Paragraphs edit controls live on `{{ attributes }}`. Never drop it from
the outermost element of a paragraph template.

```twig
<div{{ attributes }}>
  <div class="prose">{{ content.field_body|field_value }}</div>
</div>
```

### Keep `{{ region_attributes.NAME }}` on layout region elements

```twig
<section{{ attributes }}>
  <div{{ region_attributes.content }}>{{ content.content }}</div>
</section>
```
Dropping it breaks LP "Add component" buttons.

### `with VAR only` on includes

Pass an explicit context and isolate it. The one bare `with header` in the
article collection is legacy; new includes use `only`:
```twig
{% include '@components/map/map.twig' with { 'map_id': id, 'center': c } only %}
```

### No `{# #}` comments inside a `with { }` hash

A comment inside the hash literal is parsed as part of the map and throws a
`TwigException`. Put the comment **above** the tag.

---

## Map Component

`map.twig` is configured entirely via data attributes; all rendering is in
`map.js`. Tile provider default: USGS National Map (free, no API key, feet).

```twig
{% include '@components/map/map.twig' with {
  'map_id':      'unique-id',
  'center':      '44.593,-71.918',
  'zoom':        12,
  'interactive': 'true',        {# 'false' for hero background map #}
  'markers':     markers,        {# [{lat,lon,color,label}] #}
  'lines':       lines,          {# [{coords,color,weight,dash}] #}
  'tiles':       'usgs-topo',     {# usgs-topo | osm | open-topo | esri-topo #}
} only %}
```

Tile config priority in `map.js`: `drupalSettings.trailMapper` →
`data-map-tiles` attribute → `usgs-topo` default.

After init: `window._surfaceMaps[map_id]`, `window._surfaceTracks[map_id]`
(raw `[lon, lat, ele_ft]`), and a `surface-map-ready` CustomEvent with
`{ map_id, map, coords }`. The event key is **`map_id`** (snake_case), never
`mapId`. GeoJSON Z values are stored in **meters** by GeoShapeConverter and
converted client-side via `drupalSettings.trailMapper.elevationUnit`.

Map containers need an explicit height — the `.map` div fills 100% of its
parent:
```css
.hero__map-bg { position: absolute; inset: 0; }
.hero__map-bg .map { height: 100%; width: 100%; }
.map-section__map-container { height: 440px; position: relative; }
.map-section__map-container .map { height: 100% !important; }
```

**Never** use the Drupal Leaflet module formatter on article pages —
`schema_geo` must be **hidden** in the article view display, or a second rogue
map renders.

### Components that need the map instance listen for the event

`elevation-profile.js` and trip overlays must wait for `surface-map-ready` —
never read `window._surfaceMaps[map_id]` synchronously on load:
```javascript
window.addEventListener('surface-map-ready', function (e) {
  if (e.detail.map_id !== myMapId) return;
  var map = e.detail.map;
});
```

---

## Button Element

Three modifiers in `source/patterns/elements/button/`:

| Modifier | Use |
|---|---|
| `--primary` | `var(--forest)` fill — primary actions |
| `--ghost` | bordered ghost — secondary actions |
| `--trail` | `var(--trail)` fill — bike/ski-context actions |

---

## The Section Frame — shared class vs. slot component

Two distinct things share the `section` name. Don't conflate them:

- **`.section` (CSS, in `section.css`)** — the shared *frame*: `padding 80px 48px`,
  `border-bottom`, the `--alt` surface tint, `.section__eyebrow` spacing, and the
  responsive padding drop. **Any block can wear it.** Change the frame once here
  and every section follows.
- **`section.twig` (component)** — frame + eyebrow + a **card-grid slot**
  (`section__grid`). For repeated-component sections only (writing, places),
  filled via `{% embed %}` + `{% block content %}`.

A block that wants the *frame* but has its own bespoke layout (not a card grid)
should **wear the class, not use the embed**:

```twig
{# about.twig — shares the frame, renders its own 1fr 1.5fr grid #}
<section class="about section{{ modifier ? ' ' ~ modifier }}" id="about">
  <div class="section__eyebrow">
    {% include '@elements/eyebrow/eyebrow.twig' with { eyebrow: { text: label } } only %}
  </div>
  <div class="about__grid"> … </div>
</section>
```

Such a component must **depend on `surface/section`** in `surface.libraries.yml`
so the frame CSS loads on the live site (Storybook hides the omission because
`styles.css` globs everything). `about`, `map-section`, and `contact` are the
frame-wearing kind; `writing`/`places` are the embed-slot kind.

### Frame variants

The frame has skin variants, applied as modifiers alongside `.section`:

| Variant | Skin | Eyebrow modifier | Used by |
|---|---|---|---|
| (base) | light, `80px 48px`, bottom border | `.eyebrow` (muted) | writing, about |
| `--alt` | `--surface2` tint | `.eyebrow` (muted) | places |
| `--invert` | `--bright` dark, centered, `100px`, no border | `.eyebrow--invert` | contact |

A dark frame's eyebrow color is an **element modifier** (`.eyebrow--invert`),
never a `.section--invert .eyebrow { … }` descendant override — the frame must
not reach in and restyle a child component's bare selector. Add new skins as
`.section--x` in `section.css`, and any matching eyebrow color as `.eyebrow--x`
on the element.

**Do not** route a frame-wearer through the `section.twig` embed — nesting its
bespoke grid inside `section__grid` doubles the wrapper and produces two
eyebrows / double padding.

---

## Layout Shells & Slots

Layout shells (`site-header`, `site-container`) own the landmark and expose
`{% block %}` slots with **default content**, so they render on a plain
`include`/Storybook render and can be customized via `{% embed %}` + block
override.

```twig
{# site-header.twig — shell with a default-filled slot #}
<header id="header" class="site-header" role="banner">
  <div class="site-header__container">
    <a href="{{ site_url|default('/') }}" class="site-header__logo">…</a>
    {% block navigation %}
      {% include '@layouts/site-navigation/site-navigation.twig' with { 'items': items } only %}
    {% endblock %}
  </div>
</header>
```

- Default block content must render from the standard props (stories don't embed).
- Do **not** put `only` on an `{% embed %}` whose block override needs the
  consumer's vars — put `only` on the inner `include` instead.
- Slot granularity matches the component; don't invent blocks the component
  doesn't actually separate.

### Variant styling — non-negotiable

- A component variant's CSS lives in the **component's own** `.css`, never in a
  layout library. `nav.twig` always attaches `surface/nav`, so `.nav--light` in
  `nav.css` loads wherever nav renders. Defining it in a layout CSS makes it
  work in Storybook only (everything is bundled there) and do nothing on the
  real site.
- When a layout includes a component and needs it to look different, add a
  **modifier** — never restyle bare component selectors (`.nav`, `.map`) from a
  layout. Bare selectors leak to every instance (see `styles.css` globbing in
  `source/CLAUDE.md`).
- Don't overload one prop for two purposes — split unrelated states into
  separate props.

---

## surface.libraries.yml Pattern

Every pattern file gets its own library entry. The library key is
`surface-{name}` matching the component directory. Components attach their own
library via `{{ attach_library('surface/{name}') }}` inside the `.twig`.

```yaml
surface-map:
  css:
    component:
      dist/css/map.css: {}
  js:
    dist/js/map.js: {}

surface-article:
  css:
    component:
      dist/css/article.css: {}
  dependencies:
    - surface/article-map-section

surface-article-map-section:
  css:
    component:
      dist/css/article-map-section.css: {}
  dependencies:
    - surface/map
    - surface/elevation-profile
```

`attach_library` is safe in component `.twig` files (Storybook mocks it) and in
`surface_preprocess_*()` via `$variables['#attached']['library'][]`. **Never**
call it in `node--*.html.twig` or `page--*.html.twig`.

---

## Common Commands

```bash
lando npm run build          # compile source → dist/ (lint + vite)
lando npm run watch          # Storybook (localhost:6006) + Vite watch
lando npm run lint:fix       # Biome JS auto-fix
lando npm run stylelint:fix  # CSS auto-fix

lando drush cr               # clear cache after template changes
lando drush cex              # export config after UI changes
lando drush cim -y           # import config
git add dist/ && git commit  # dist/ MUST be committed (Pantheon has no build step)
```

---

## Accessibility Resources

- [Axe: Color Contrast](https://dequeuniversity.com/rules/axe/4.11/color-contrast?application=axeAPI)
- [Axe: Link in Text Block](https://dequeuniversity.com/rules/axe/4.11/link-in-text-block?application=axeAPI)

---

## What NOT To Do

- No `@components/surface/` paths — old nested structure, removed
- No SDC — Surface does not use Single-Directory Components; never create
  `*.component.yml` files or add `props:`/`examples:` schema sections to fixtures
- No `surface-` CSS class prefix — components use flat BEM (`.map`, `.hero__content`)
- No `|field_value`, `drupal_field()`, `drupal_view()` in `source/patterns/` —
  Drupal-only, crashes Storybook (fine in `templates/`)
- No `{# #}` comments inside a `with { }` hash literal — breaks the Twig parser
- No `only` on an `{% embed %}` whose block override needs the consumer's vars
- No `{{ attach_library() }}` in `node--*.html.twig` / `page--*.html.twig`
- No component-variant CSS in a layout library — variant styles live in the
  component's own `.css`
- No restyling bare component selectors (`.nav`, `.map`) from a layout — scope
  behind a modifier
- No dropping `{{ attributes }}` from paragraphs or `{{ region_attributes.NAME }}`
  from layout regions — breaks Layout Paragraphs
- No `once()` in map/elevation JS — use the IIFE dual-context pattern (STORYBOOK.md)
- No hardcoded hex — always `var(--token)`
- No `text-transform: uppercase` on Bebas Neue (already all-caps); only on
  DM Mono labels where uppercase is a deliberate choice
- No Drupal Leaflet formatter on article pages — hide `schema_geo` in the view display
- No creating content types before dependencies (DefinedTerm → ImageObject →
  Place → BlogPosting → TouristTrip)
- No programmatic field creation — Blueprints UI + `lando drush cex`
- No git commands — Sean handles all git operations
- No editing `dist/` directly — but `dist/` MUST be committed
- Vite font output path is `../fonts/`, never `../assets/fonts/`
