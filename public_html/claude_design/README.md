# seanmontague.com — Design System Reference

## About the Site

Personal editorial site for Sean Montague — web developer at the
Smithsonian NMNH, based in Burke, Vermont. Covers Kingdom Trails
mountain biking, Burke Mountain skiing, permaculture and food forest
work, and Leaflet-based interactive mapping. Not a portfolio.
Editorial outdoor/travel aesthetic — think Monocle or Kinfolk, not REI.

The design system is called **Surface**.

---

## Typography

| Role | Font | Usage |
|---|---|---|
| Display | Bebas Neue | Hero titles, stats values, section headings, card names |
| Serif | Cormorant Garamond | Body text, article prose, pull quotes, subtitles |
| Mono | DM Mono | Labels, nav, metadata, dates, tags, badges, captions |

Body text: 19px, line-height 1.78, font-weight 300 (Cormorant reads
best at light weight). Article titles: `clamp(40px, 6vw, 80px)`.

---

## Color Palette

```
--bg:       #f7f6f2   warm off-white — page background
--surface:  #ffffff   card and panel backgrounds
--surface2: #f0ede6   slightly darker surface — sidebar headers, stats bg
--border:   #e0dbd1   default borders
--border-dark: #c8c2b8  hover/active borders
--muted:    #736e62   secondary text, labels, metadata
--text:     #2c2a25   primary body text
--bright:   #161410   near-black — headings, high contrast text
--forest:   #3a5a40   primary green — links, hover accents, trails
--trail:    #7a3410   brown — mountain biking, skiing content
--sky:      #4a7c9e   blue — maps and mapping content
--stone:    #6b6560   neutral — general/uncategorized
--amber:    #a05a00   difficulty badge text
--amber-bg: #fff3e0   difficulty badge background
--amber-border: #f0d090  difficulty badge border
```

---

## Content Categories & Accent Colors

Each content category has a distinct accent color used for badges,
category labels, and border-left hover accents on cards.

| Category | Color token | Hex |
|---|---|---|
| Kingdom Trails | `--trail` | #7a3410 |
| Burke Mountain | `--trail` | #7a3410 |
| Maps | `--sky` | #4a7c9e |
| Drupal | `--stone` | #6b6560 |
| Permaculture | `--forest` | #3a5a40 |
| Travel | `--sky` | #4a7c9e |

---

## Design Principles

**No gradients.** Background colors are flat.

**No rounded corners.** All elements use sharp 90° corners.

**No drop shadows** except a very subtle card elevation
(`box-shadow: 0 2px 8px rgba(0,0,0,0.08)`).

**Border-left hover accent.** Cards, list items, and nav links use:
```css
border-left: 3px solid transparent;
transition: border-left-color 0.2s ease;
/* on hover: */
border-left-color: var(--forest); /* or category color */
```

**Generous whitespace.** Section padding: `80px 48px`. Content max-width:
`720px` (article body), `1100px` (wide/header containers).

---

## Component Patterns

### Cards
3-column grid, bordered, no gap — borders form the grid lines.
Card hover: background shifts to `--bg`, border-left accent appears.
Category label (DM Mono, 9px, uppercase) → Title (Cormorant, 21px) →
Excerpt (Cormorant, 16px, weight 300) → Meta (DM Mono, 9px).

### Stats Bar
Horizontal grid of stat items. Each: label (DM Mono 9px uppercase),
value (Bebas Neue 28px), unit (DM Mono 9px). Separated by `--border`.

### Article Header
Eyebrow: DM Mono 9px uppercase in category color.
Difficulty badge: amber background, DM Mono 9px.
Title: Bebas Neue, `clamp(40px, 6vw, 80px)`, line-height 0.95.
Subtitle: Cormorant Garamond italic, 22px, weight 300, color `--muted`.

### Sidebar Cards
Header: DM Mono 9px uppercase, `--surface2` background.
Body: white background with 18px padding.
Hover: background shifts to `--bg`, border-left forest green.

### Map Link List
Bordered list. Each item: DM Mono 11px, justify-content space-between,
arrow `→` on right. Hover: `--bg` background, border-left forest green,
arrow translates 3px right.

### Article Nav (prev/next)
Two-column border grid. Left item: border-left accent on hover.
Right item: border-right accent on hover, text-align right.

### Blockquote
`border-left: 3px solid var(--forest)`, background tint
`rgba(58,90,64,0.04)`, padding 16px 24px.
Quote text: Cormorant italic 22px. Cite: DM Mono 9px uppercase.

### Drop Cap
First letter of lead paragraph: Bebas Neue, 4.2em, float left,
line-height 0.78. Applied via `.drop-cap` class on the paragraph.

### Reading Progress Bar
2px fixed bar at top of viewport, `--forest` color, tracks scroll %.

---

## Page Types

**Article** — header + stats bar (for trail reports) + full-width
Leaflet map + elevation profile + body + sidebar.

**Trip** — hero image (68vh) + title/dates + destination strip
(scrollable row of place names) + trip stats (4-column: duration,
places, distance, counties) + route map with numbered markers +
dashed polyline + body narrative + itinerary sidebar + destination
cards grid with mini Leaflet maps.

**Homepage** — hero Leaflet map (100vh, non-interactive) + marquee
strip + featured map section + article cards grid + places grid +
about section + contact (dark background).

---

## Leaflet Map Style

Tile set: OpenTopoMap for Ireland/international content, USGS National
Map for US content. Map popups: DM Mono 11px, no border-radius,
no popup tip arrow. Markers: colored dot pins with white border.
Route lines: `--forest` color, weight 3, opacity 0.9. Dashed route
polylines on trip maps: dashArray `6 6`.

---

## Theme Architecture

Surface uses a modified Atomic Design hierarchy with custom naming:

| Surface name | Atomic Design equivalent | Rule |
|---|---|---|
| Elements | Atoms | Smallest units — no includes |
| Components | Molecules | May include Elements only |
| Collections | Organisms | May include Components + Elements |
| Layouts | Templates | Drupal region wrappers |
| Pages | Pages | Full Storybook demos |

CSS uses BEM throughout. JS uses ES6.

### Available patterns (seanmontague.com build)

**Elements:** Button, Byline, Date, Eyebrow, Images, Links, List,
Pager, Quote, Readtime, Skip-link, Text, Title, Video,
Reading Progress Bar (new)

**Components:** About, Breadcrumb, Card, Contact, Elevation Profile,
Footer, Hero, Map, Map Link List (new), Marquee, Nav, Places Card,
Stats Bar

**Collections:** Article, Article Header, Article Map Section,
Map Section, Trip (new)

**Layouts:** Block, Site Container, Site Header, Site Footer,
Site Navigation, Site Homepage

---

## Tone & Voice

Editorial, personal, unhurried. First person. Landscape-focused.
Precise about terrain, place names, and conditions. Not promotional.
Reads like field notes or a well-edited travel journal.