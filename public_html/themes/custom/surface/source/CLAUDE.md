# Surface Source Directory

Read `public_html/themes/custom/surface/CLAUDE.md` first — it is the
authoritative reference for theme architecture, Drupal integration, content
model, and the map component. Read `../STORYBOOK.md` for the rules that keep
Storybook from breaking (JS dual-context, Twig restrictions, YAML fixtures).

This directory is the Storybook-driven design-system source. Build output goes
to `dist/`. **Never edit `dist/` directly.**

---

## Directory Structure

```
source/
├── assets/
│   ├── fonts/         # Self-hosted woff2/ttf — Bebas Neue, Cormorant Garamond, DM Mono
│   └── images/        # SVGs
├── props/             # Design tokens (import order set by index.css)
│   ├── index.css      # @imports all props — nek.css LAST so it wins
│   ├── media.css      # breakpoints
│   ├── fonts.css      # base font sizes/weights/leading/tracking + generic stacks
│   ├── sizes.css      # spacing scale (--size-*)
│   ├── easing.css     # easing curves
│   ├── zindex.css     # z-index scale
│   ├── shadows.css    # --shadow-*
│   ├── aspects.css    # aspect ratios
│   ├── animations.css # keyframe tokens
│   ├── borders.css    # --border-size-*, --radius-*
│   ├── brand.css      # generic brand scaffold
│   ├── colors.css     # full HSL color ramps (--gray-*, --green-* etc.)
│   └── nek.css        # ★ NEK palette + font stacks — overrides everything
└── patterns/
    ├── styles.css     # Storybook-only glob bundle (see below)
    ├── storybook.css  # Storybook UI/demo overrides
    ├── base/          # reset, global, typography, utilities, @font-face
    ├── elements/      # Atomic — include nothing
    ├── components/    # Composed — may include @elements/ only
    ├── collections/   # Composed — may include @components/ + @elements/
    ├── layouts/       # Drupal region wrappers — may include everything
    ├── pages/         # Full-page Storybook demos
    └── theme/         # Drupal admin UI overrides only
```

### Elements

`button` (`--primary`/`--ghost`/`--trail`), `byline`, `date`, `eyebrow`,
`images`, `links`, `list`, `media`, `pager`, `quote`, `reading-progress`,
`readtime`, `skip-link`, `text`, `title`, `video`.

### Components (selected)

`map` (Leaflet), `elevation-profile` (canvas chart, listens for
`surface-map-ready`), `hero` (full-bleed background map + content), `card`,
`places-card`, `stats-bar`, `nav`, `marquee`, `breadcrumb`, `page-title`,
`dest-index` + `dest-timeline` (trip destination strip + itinerary),
`map-link-list`, `about`, `contact`, `footer`, plus Drupal form wrappers
(`form-item`, `form-item-label`, `fieldset`, `input`, `checkbox`,
`search-form`, `user-login-form`, `user-pass-form`, `messages`, `tabs`).

### Collections

`article`, `article-header`, `article-map-section`, `map-section`,
`destination`, `trip`.

### Layouts

`site-container`, `site-header` (shell with `{% block navigation %}`),
`site-navigation`, `site-footer`, `site-homepage`, `page`, `region`, `block`,
`main`, `media`, `view`, `content-edit`, `layout-container`.

---

## Component File Requirements

Every pattern needs:

| File | Purpose |
|---|---|
| `name.twig` | Single twig path — same file renders in Storybook AND Drupal |
| `name.css` | Styles — must exist even if empty |
| `name.yml` | Storybook fixture / default args |

Optional:

| File | Purpose |
|---|---|
| `name.stories.jsx` | Storybook story definition |
| `name.mdx` | Documentation |

A component's variants/modifiers (`.name--x`) are styled in **`name.css`** —
never in a layout or collection stylesheet (see Gotcha #3).

---

## Class naming — flat BEM, no theme prefix

```css
/* CORRECT */
.map { }
.article__body { }
.hero--compact { }

/* WRONG */
.surface-map { }        /* no theme prefix on classes */
.myComponent { }        /* camelCase */
.article_body { }       /* snake/underscore block-element */
```

The `surface/` prefix appears only in **library names** (`attach_library('surface/map')`)
and Drupal library keys — not in CSS class names.

---

## Tokens — two-layer system

`props/index.css` imports the base design tokens, then `nek.css` **last** so
the NEK palette and font stacks win.

Use NEK semantic tokens in components; reach for base primitives (`--size-*`,
`--shadow-*`, `--font-size-*`, `--font-weight-*`, `--radius-*`) for spacing,
elevation, and type scale.

```css
/* Palette (nek.css) */
var(--bg)            /* warm off-white page background */
var(--surface)       /* card/panel background */
var(--surface2)      /* slightly darker surface */
var(--border)        /* default border */
var(--border-dark)   /* hover-state border */
var(--muted)         /* secondary text */
var(--text)          /* primary body text */
var(--bright)        /* near-black headings */
var(--forest)        /* primary green — trails, links, accents */
var(--trail)         /* brown — biking/skiing content */
var(--sky)           /* blue — maps content */
var(--stone)         /* neutral stone */
var(--amber) / var(--amber-bg) / var(--amber-border)  /* difficulty badge */

/* Fonts */
var(--font-display-nek)  /* Bebas Neue — hero titles, stat values, eyebrows */
var(--font-serif-nek)    /* Cormorant Garamond — body, article headings */
var(--font-mono-nek)     /* DM Mono — labels, nav, metadata, tags */

/* Layout widths */
var(--content-width)     /* 720px — article body column */
var(--wide-width)        /* 1100px — article/header container max */
```

`nek.css` also remaps `--font-primary` → serif and `--font-secondary` →
display, so base typography inherits the NEK faces automatically.

---

## styles.css — glob bundle (Storybook only)

`patterns/styles.css` is used **only in Storybook** to combine all CSS into one
sheet. Props are imported via `base/base.css` (`@import "../../props/index"`),
then the pattern globs run:

```css
@import-glob 'base/base.css';
@import-glob 'base/utilities.css';
@import-glob 'elements/**/*.css';
@import-glob 'components/**/*.css';
@import-glob 'collections/**/*.css';
@import-glob 'layouts/**/*.css';
@import-glob 'theme/**/*.css';
```

### Globbing bundles EVERYTHING — library attachment is not scoping

Because every CSS file is glob-imported into one sheet in Storybook (and the
build bundle), a bare selector in any file is effectively global. "This file
only loads on that layout" is **false** as a scoping strategy:

- Component variants → modifier classes in the component's own CSS.
- Import order is cascade order: `components` import before `layouts`, so a
  layout's bare `.map` rule would still win over `map.css`. Another reason
  layouts must never restyle bare component selectors.

In Drupal, CSS arrives per-library via `attach_library`, but the same
leak-proofing discipline applies — selectors must stand on their own.

---

## CSS Conventions & Gotchas

Hard-won rules. Violating any of these has produced a real bug.

### 1. `box-sizing: border-box` must be GLOBAL
```css
*, *::before, *::after { box-sizing: border-box; }
```
If the reset targets only `*::before, *::after`, every element with
`width: 100%` + `padding` overflows its parent by the padding amount.

### 2. No apostrophes or quotes inside CSS comments
The PostCSS/Vite pipeline tokenizes a lone `'` in a comment as the start of a
string and fails with `Unclosed string`. This includes contractions.
```css
/* WRONG: so the default isn't affected   → "isn't" breaks the build */
/* RIGHT: so the default is not affected */
```
Quotes in actual CSS values are fine (balanced, not in a comment).

### 3. Component variant CSS lives WITH the component
`.nav--light` belongs in `nav.css` (which `nav.twig` attaches), not in a layout
CSS. A variant defined in a layout works in Storybook only (everything bundled)
and does nothing on the real site.

### 4. Scope layout-added component styling behind a MODIFIER
```css
/* WRONG (in a layout css): leaks to every map on the site */
.map { filter: grayscale(1); }

/* RIGHT: variant in map.css, opted into via modifier */
.map--muted { filter: grayscale(1); }
```

### 5. Bebas Neue is all-caps — no `text-transform: uppercase`
Bebas Neue glyphs are uppercase by design. Adding `text-transform: uppercase`
is redundant and distorts spacing — and historically masked a font-loading
404 (text looked uppercase from CSS, so nobody noticed the font fell back to
sans-serif). Only use `text-transform: uppercase` on **DM Mono** elements
(eyebrows, badges, nav labels) where uppercase is a deliberate choice on a
mixed-case face.

### 6. Variable fonts need a weight RANGE in @font-face
Cormorant Garamond is variable. Its `@font-face` declares
`font-weight: 100 900` so the single file covers all weights. If it declared a
single weight, the browser would synthesize faux bold for headings/emphasis.

### 7. Vite font output path is `../fonts/`
`@font-face` `src` is resolved relative to the compiled CSS in `dist/css/`, so
the correct path is `../fonts/NAME.woff2`. A recurring bug is Vite emitting or
referencing `../assets/fonts/` — correct it to `../fonts/`.

---

## JS — dual-context (Drupal + Storybook)

Every JS file self-initializes in both environments. Use the IIFE pattern with
both `Drupal.behaviors` and `DOMContentLoaded`, guarded for `typeof Drupal`.
**Never** use `once()` (undefined in Storybook) — use a data-attribute or
property guard. All files start with `/* jshint esversion: 6 */` (map.js uses
`esversion: 11`). Full rules and the canonical template are in `../STORYBOOK.md`.

Map JS contract (see theme-root CLAUDE.md for the full description):
- `data-*` attributes configure the map; tile priority is
  `drupalSettings.trailMapper` → `data-map-tiles` → `usgs-topo`.
- After init: `window._surfaceMaps[map_id]`, `window._surfaceTracks[map_id]`,
  and a `surface-map-ready` event with `{ map_id, map, coords }`.
- Consumers (`elevation-profile.js`) **listen for the event** — never read the
  map registry synchronously on load.
- `elevation-profile.js` skips rendering when all Z values are 0 (driving /
  My-Maps routes have no real elevation).

---

## Storybook / Drupal Data Contract

The `.yml` file is the contract. Drupal preprocess arrays must match yml keys.
The **same** `.twig` renders in both environments — no `isDrupal` flag, no dual
render paths.

```
Storybook: name.yml → name.twig
Drupal:    preprocess hook → node--*.html.twig → name.twig
```

### This is NOT SDC

Surface does **not** use Drupal Single-Directory Components. There are no
`*.component.yml` files and the `.yml` fixtures carry no `props:` or `examples:`
schema sections — they are flat arg maps read directly by the stories (e.g.
`map.yml` → `map_id`, `center`, `markers`). Do **not** create `*.component.yml`
files, add `props:`/`examples:` wrappers, or restructure fixtures to the SDC
shape. The contract is: yml keys === the variables `name.twig` reads === the
preprocess array keys. Keep it flat.

Stories title prefix matches the hierarchy level: `Elements/`, `Components/`,
`Collections/`, `Layouts/`, `Pages/`, `Base/`. Every new pattern needs a
`.stories.jsx` (or it's invisible in Storybook) and a `.yml` (or args are
undefined). Multi-line arrays in fixtures need deep indentation — see the YAML
rules in `../STORYBOOK.md`.

---

## Typography & Fonts

Defined in `patterns/base/fonts/fonts.css` (`@font-face`), with family stacks
in `props/nek.css`. Self-hosted, copied to `dist/fonts/` by Vite.
`font-display: swap` on all faces.

### Bebas Neue — display
All-caps, single weight. `var(--font-display-nek)`. Hero titles, stat values,
eyebrows. No `text-transform: uppercase` (Gotcha #5).

### Cormorant Garamond — serif
Variable font, `font-weight: 100 900`, normal + italic files.
`var(--font-serif-nek)`. Article body and headings, taglines.

### DM Mono — mono / labels
Light (300), Regular (400), Medium (500) + italics. `var(--font-mono-nek)`.
Nav, eyebrows, metadata, tags, stat labels. Uppercase here is a deliberate CSS
choice where wanted.

---

## Build Commands

```bash
lando npm run build    # compile source → dist/
lando npm run watch    # Storybook (localhost:6006) + Vite watch
lando drush cr         # clear Drupal cache after template changes
```

---

## Deployment (Pantheon)

Pantheon runs `composer install` only — no `npm run build`. The compiled
`dist/` **must be committed**:

```
edit source/ → lando npm run build → git add dist/ → commit → push (Sean does git)
```

### What NOT to commit
- `node_modules/` — never
- `storybook-static/` — Storybook build output, not needed on Pantheon
- `source/` ships for reference but is not required at runtime — only `dist/`
  is served

If `.gitattributes` / line-ending issues appear with any binary-as-`.css`
Vite artifacts, enforce `*.css text eol=lf` to prevent CRLF build failures.
