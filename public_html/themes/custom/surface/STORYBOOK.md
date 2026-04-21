# Surface Storybook — Rules for Claude Code

This file documents what Claude Code must and must not do when working
on the Surface theme to avoid breaking Storybook.

## Scope — CRITICAL

**Claude Code must only ever touch files inside the `source/` directory.**

Files outside `source/` that Claude Code must never modify:
- `dist/` — compiled output, overwritten on every build
- `node_modules/` — package dependencies
- `surface.info.yml`, `surface.theme`, `surface.routing.yml`
- `surface.libraries.yml` — only edit this when explicitly instructed
- `vite.config.js` — only edit this when explicitly instructed
- `templates/` — Drupal node/paragraph/field templates (separate from patterns)
- `package.json`, `package-lock.json`

If a task requires changes outside `source/`, stop and ask before proceeding.
The `source/` directory is the only safe working area for Storybook-related work.

---

Read this file before making any change to:
- `source/patterns/**` (any component, collection, layout, element, or page)
- `source/props/*.css` (design tokens)
- `vite.config.js` (only when explicitly instructed)
- `surface.libraries.yml` (only when explicitly instructed)
- Any `.js` file in the theme

---

## How Storybook runs

Storybook runs at `http://localhost:6006` via `lando npm run watch`.
It renders Twig templates directly using `twig.js`. It does NOT run
Drupal. There is no `drupalSettings`, no `Drupal.behaviors`, no
`once()`, no Drupal asset pipeline.

Each pattern has up to five files:
```
component-name/
  component-name.twig     ← rendered by Storybook
  component-name.css      ← compiled by Vite, loaded globally
  component-name.js       ← must self-initialize (see JS rules)
  component-name.yml      ← provides default args to stories
  component-name.stories.jsx ← Storybook story definitions
```

---

## Twig rules

### Namespaces are flat — never nested

```twig
{# CORRECT #}
{% include '@components/map/map.twig' %}
{% include '@elements/button/button.twig' %}
{% include '@collections/article/article.twig' %}

{# WRONG — old nested path, breaks Storybook #}
{% include '@components/surface/map/map.twig' %}
```

Twig namespaces are defined in `vite.config.js`. Never add a middle
segment. The pattern is always `@{level}/{name}/{name}.twig`.

### Pattern hierarchy — never include upward

| Level | May include |
|---|---|
| Elements | Nothing |
| Components | `@elements/` only |
| Collections | `@components/` and `@elements/` |
| Layouts | `@collections/`, `@components/`, `@elements/` |
| Pages | Anything |

Breaking this hierarchy causes circular includes or missing template
errors in Storybook.

### Never apply `matches` to a field object

`matches` requires a string. Always traverse to the scalar value first:

```twig
{# WRONG — crashes: matches applied to FieldItemList object #}
{% set is_gpx = node.schema_geoshape matches '/\.gpx$/i' %}

{# WRONG — crashes: entity is a media object, not a string #}
{% set is_gpx = node.schema_geoshape.entity matches '/\.gpx$/i' %}

{# CORRECT — traverse all the way to the filename string #}
{% set geo_media = node.schema_geoshape.entity %}
{% set geo_file  = geo_media ? geo_media.field_media_file.entity : null %}
{% set is_gpx    = geo_file and geo_file.filename.value matches '/\.gpx$/i' %}
{# NOTE: geo_file.filename is a FieldItemList in Drupal — .value unwraps
   to the scalar string. Never apply matches to the field object directly. #}
```

`schema_geoshape` is a `data_download` media entity reference. The file
is at `schema_geoshape → media entity → field_media_file → file entity`.
`geo_file.filename` is the string. Never shortcut this traversal.

### Never use Drupal-only Twig filters or functions in pattern templates

These do not exist in `twig.js` and will crash Storybook:
- `|field_value` — only use in Drupal node/paragraph templates under `templates/`
- `drupal_field()`, `drupal_entity()`, `drupal_view()`
- `attach_library()` — safe in Twig but a no-op in Storybook; do not
  rely on it for CSS/JS in stories

Pattern templates (under `source/patterns/`) must render purely from
the variables passed to them. No Drupal API calls.

---

## JS rules

### Every JS file must handle both Drupal and Storybook contexts

The dual-context pattern is mandatory. Never use only `Drupal.behaviors`
or only `DOMContentLoaded`:

```javascript
/* jshint esversion: 6 */

(function () {
  'use strict';

  function init(context) {
    context.querySelectorAll('.my-component:not([data-init])').forEach(function (el) {
      el.setAttribute('data-init', '1');
      // ... init code
    });
  }

  // Drupal context
  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    Drupal.behaviors.myComponent = {
      attach: function (context) { init(context); },
    };
  }

  // Storybook / standalone context
  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
    // Storybook re-renders without a full DOMContentLoaded — also run immediately
    if (document.readyState !== 'loading') { init(document); }
  }
})();
```

### Never use `once()` — use a data attribute guard instead

`once()` is a Drupal utility that does not exist in Storybook:

```javascript
// WRONG — breaks Storybook
Drupal.behaviors.myThing = {
  attach: function (context) {
    once('my-thing', '.my-component', context).forEach(function (el) { ... });
  },
};

// CORRECT — use a data attribute guard
if (el._myComponentInit) return;
el._myComponentInit = true;
```

### Never use `window.drupalSettings` without a null guard

```javascript
// WRONG — crashes if drupalSettings is undefined (Storybook)
const tileKey = drupalSettings.trailMapper.tileKey;

// CORRECT
const ds = window.drupalSettings?.trailMapper;
const tileKey = ds?.tileKey ?? 'usgs-topo';
```

### Never access `Drupal` without checking it exists first

```javascript
// WRONG
Drupal.behaviors.myThing = { ... };

// CORRECT
if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
  Drupal.behaviors.myThing = { ... };
}
```

### Map JS: use the surface-map-ready event, not direct init

`map.js` dispatches `surface-map-ready` with `{ map_id, map, coords }`
after Leaflet initializes. Components that need the map instance (e.g.
elevation profile, trip route overlay) must listen for this event:

```javascript
window.addEventListener('surface-map-ready', function (e) {
  if (e.detail.map_id !== myMapId) return;
  var map = e.detail.map;
  // add layers, markers, etc.
});
```

Never try to read `window._surfaceMaps[map_id]` synchronously on page
load — the map may not exist yet.

---

## CSS rules

### Always use BEM

```css
/* CORRECT */
.my-component { }
.my-component__element { }
.my-component--modifier { }

/* WRONG */
.myComponent { }
.my_component_element { }
```

### Always use design tokens, never hardcoded hex values

```css
/* CORRECT */
color: var(--forest);
border-color: var(--border);
background: var(--surface2);

/* WRONG */
color: #3a5a40;
background: #f0ede6;
```

All tokens are defined in `source/props/nek.css`. Current palette:
```
--bg, --surface, --surface2, --border, --border-dark
--muted, --text, --bright, --forest, --trail, --sky, --stone
--amber, --amber-bg, --amber-border
--font-display-nek, --font-serif-nek, --font-mono-nek
--content-width: 720px, --wide-width: 1100px
```

### Never add CSS to a pattern file that belongs in another pattern

If you're styling a `.card__title` inside `article.css`, move it to
`card.css` instead. Each pattern owns its own BEM block.

---

## Stories rules

### Every new pattern needs a `.stories.jsx` file

Without a stories file, the pattern does not appear in Storybook.

Minimum story structure:

```jsx
import myComponent from './my-component.twig';
import data from './my-component.yml';

const settings = {
  title: 'Components/My Component',  // level matches pattern hierarchy
};

export const Default = {
  render: (args) => myComponent(args),
  args: { ...data },
};

export default settings;
```

### Title must match the pattern hierarchy level

| Directory | Title prefix |
|---|---|
| `elements/` | `Elements/` |
| `components/` | `Components/` |
| `collections/` | `Collections/` |
| `layouts/` | `Layouts/` |
| `pages/` | `Pages/` |
| `base/` | `Base/` |

### Every new pattern needs a `.yml` fixture file

The yml provides default args for the story. If the pattern accepts
no variables, use an empty yml:

```yaml
{}
```

If the yml is missing, the story will render with no args and may
crash with undefined variable errors.

### Storybook does not load Leaflet automatically

Map stories work because Leaflet is loaded via a Storybook preview
configuration. Do not add new CDN script tags to individual stories.
If a new component requires an external library, add it to the
Storybook preview file, not the component.

---

## Libraries and Vite rules

### Every new pattern CSS/JS file needs a library entry in surface.libraries.yml

```yaml
surface-my-component:
  css:
    component:
      dist/css/my-component.css: {}
  js:
    dist/js/my-component.js: {}
```

The library key must be `surface-{component-name}` matching the
component directory name. The dist paths are produced by Vite.

### Never edit `dist/` directly

All files in `dist/` are compiled output. Edit source files under
`source/` and run `lando npm run build` or `lando npm run watch`.

### Never change `vite.config.js` Twig namespace aliases

The namespace aliases map `@components` → `source/patterns/components`
etc. Changing these breaks every single Twig include in Storybook.
If you need to add a new namespace, add an entry — do not change
existing entries.

---

## Before running any build command

Run `lando npm run watch` and open Storybook at http://localhost:6006
to verify the current state before making changes. If Storybook is
already broken before your changes, note this and do not proceed
without flagging it.

After making changes, verify in Storybook that:
1. The new/changed component renders without console errors
2. Existing components in the same level are unaffected
3. Any page stories that include the changed component still render

---


---

## YAML fixture rules

### Multi-line arrays in `.yml` files require deep indentation

When a YAML value is a multi-line flow sequence (e.g. coordinate arrays),
continuation lines MUST be indented beyond the key. The most common failure
is `coords` inside a list item — the list item adds one level, the key adds
another, so values need at least 6 spaces:

```yaml
# CORRECT — 6 spaces on continuation lines
map_lines:
  - coords: [
      [-71.945434,44.588812],[-71.945209,44.588583],
      [-71.944501,44.588152],[-71.943888,44.587773]
    ]
    color: '#3a5a40'
    weight: 3

# WRONG — 4 spaces causes YAMLException: deficient indentation
map_lines:
  - coords: [
    [-71.945434,44.588812],[-71.945209,44.588583],
    [-71.944501,44.588152],[-71.943888,44.587773]
  ]
    color: '#3a5a40'
```

The rule: continuation lines inside a block sequence item (`- key: [`)
must be indented to at least the key column + 2. With 2-space list indent
and 2-space key indent, that means **6 spaces minimum** for the values,
and **4 spaces** for the closing `]`.

This applies to all multi-line arrays in fixture files:
`map_lines`, `map_markers`, `elev_data`, `coords`, `markers` etc.

When in doubt, put the entire array on one line or use the
YAML block sequence format instead of flow sequence.

## Common ways Storybook breaks — do not do these

| Action | Why it breaks |
|---|---|
| Use `once()` in component JS | `once` is undefined in Storybook |
| Use `|field_value` in pattern Twig | Filter doesn't exist in twig.js |
| Apply `matches` to a field object | Must apply to a string — always traverse to the scalar value first |
| Hardcode `drupalSettings.X` without null guard | Crashes when undefined |
| Change a Twig namespace in vite.config.js | All includes using that namespace break |
| Add `@components/surface/` prefix to includes | Old nested path, never correct |
| Call `Drupal.behaviors.X` without checking `typeof Drupal` | Crashes in Storybook |
| Create a component without a `.yml` fixture | Story args are undefined |
| Create a component without a `.stories.jsx` | Component invisible in Storybook |
| Edit `dist/` files directly | Overwritten on next build |
| Include a component at the wrong hierarchy level | Circular includes or missing template |
