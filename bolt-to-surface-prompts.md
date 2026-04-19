# Bolt → Surface: Design Integration Prompts

Reference files created by Bolt.new:
- `public_html/project/public/seanmontague.html`
- `public_html/project/public/seanmontague-article.html`
- `public_html/project/public/ireland-2024.html`

These five steps integrate Bolt's design refinements into the Surface
theme pattern library. Run each in order and verify in Storybook before
proceeding to the next.

Storybook runs at http://localhost:6007 via `lando npm run watch`

---

## Step A — Design tokens (nek.css)

```
Read public_html/themes/custom/surface/CLAUDE.md.
Read public_html/themes/custom/surface/source/props/nek.css.
Read public_html/project/public/seanmontague.html (the :root block).

Add the following missing tokens to nek.css. Place them logically
alongside existing tokens — colors with colors, layout with layout.

New color tokens (add to palette block):
  --border-dark: #c8c2b8;       /* darker border for hover states */
  --amber:       #a05a00;       /* difficulty badge text */
  --amber-bg:    #fff3e0;       /* difficulty badge background */
  --amber-border: #f0d090;      /* difficulty badge border */

New layout tokens (add after font stacks):
  --content-width: 720px;       /* article body column width */
  --wide-width:    1100px;      /* max-width for article/header containers */

Note: --amber, --amber-bg, --amber-border are already used in
article-header.css as hardcoded values. After adding them as tokens,
update article-header.css to reference the variables instead of
the hardcoded hex values.

Show the full updated nek.css and the article-header.css diff
before writing.

After: lando npm run build
Verify: Storybook → Base → Brand shows updated tokens
```

---

## Step B — CSS tweaks to existing components

```
Read public_html/themes/custom/surface/CLAUDE.md.
Read public_html/themes/custom/surface/source/patterns/components/card/card.css.
Read public_html/themes/custom/surface/source/patterns/collections/article/article.css.
Read public_html/themes/custom/surface/source/patterns/components/places-card/places-card.css.
Read public_html/project/public/seanmontague.html.
Read public_html/project/public/seanmontague-article.html.

Three targeted CSS changes — no structural changes, additions only.

Change 1 — card.css:
  a) Add border-left accent to .card:
       border-left: 3px solid transparent;
  b) Add to .card:hover:
       border-left-color: var(--forest);
  c) Bump .card__excerpt font-size from 14px → 16px.
     (Bolt note: "Cormorant needs room to breathe at smaller sizes")

Change 2 — article.css:
  a) Add background tint to .article__body blockquote:
       background: rgba(58, 90, 64, 0.04);
       padding: 16px 24px;  /* adjust existing padding to match Bolt */
  b) Add border-left accent to .article-nav__item:
       border-left: 3px solid transparent;
     Add to .article-nav__item:first-child:hover:
       border-left-color: var(--forest);
     Add to .article-nav__item:last-child:
       border-right: 3px solid transparent;
       border-left: none;
       text-align: right;
     Add to .article-nav__item:last-child:hover:
       border-right-color: var(--forest);

Change 3 — places-card.css:
  a) Add to .places-card:
       border-left: 3px solid transparent;
       transition: border-color 0.2s ease, border-left-color 0.2s ease;
  b) Add to .places-card:hover:
       border-left-color: var(--forest);

Show each changed file before writing.
After: lando npm run build
Verify in Storybook: cards have border-left accent on hover,
blockquote has background tint, article nav has green accent.
```

---

## Step C — Drop cap + reading progress bar

```
Read public_html/themes/custom/surface/CLAUDE.md.
Read public_html/themes/custom/surface/source/patterns/collections/article/article.css.
Read public_html/themes/custom/surface/source/patterns/collections/article/article.twig.
Read public_html/project/public/seanmontague-article.html.

Two additions:

Addition 1 — Drop cap (article.css + article.twig):

CSS — add to article.css:
  .drop-cap::first-letter {
    color: var(--bright);
    float: left;
    font-family: var(--font-display-nek);
    font-size: 4.2em;
    line-height: 0.78;
    margin-right: 8px;
    margin-top: 6px;
  }

Twig — the drop cap is opt-in via a class on the first paragraph.
In article.twig, the body is rendered via {{ body|raw }}.
No Twig change needed — the class is applied in the body HTML itself.
Document this in a Twig comment above the body render.

Addition 2 — Reading progress bar (new element):

Create these files:
  source/patterns/elements/reading-progress/reading-progress.twig
  source/patterns/elements/reading-progress/reading-progress.css
  source/patterns/elements/reading-progress/reading-progress.js
  source/patterns/elements/reading-progress/reading-progress.yml

reading-progress.twig:
  {{ attach_library('surface/reading-progress') }}
  <div class="reading-progress" id="reading-progress" role="progressbar"
       aria-label="Reading progress" aria-valuenow="0"
       aria-valuemin="0" aria-valuemax="100"></div>

reading-progress.css:
  .reading-progress {
    background: var(--forest);
    height: 2px;
    inset-block-start: 0;
    inset-inline-start: 0;
    position: fixed;
    transition: width 0.1s linear;
    width: 0%;
    z-index: 300;
  }

reading-progress.js:
  /* jshint esversion: 6 */
  (function () {
    const bar = document.getElementById('reading-progress');
    if (!bar) return;
    window.addEventListener('scroll', () => {
      const scrollTop = window.scrollY;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      bar.style.width = pct + '%';
      bar.setAttribute('aria-valuenow', Math.round(pct));
    });
  })();

reading-progress.yml (Storybook fixture):
  {}

Add the library entry to surface.libraries.yml:
  reading-progress:
    css:
      component:
        dist/css/reading-progress.css: {}
    js:
      dist/js/reading-progress.js: {}

Add reading-progress to the article collection include in
article.twig so it renders on article pages.

Show all new/changed files before writing.
After: lando npm run build
Verify in Storybook: article page shows green progress bar on scroll.
```

---

## Step D — Map link list component

```
Read public_html/themes/custom/surface/CLAUDE.md.
Read public_html/themes/custom/surface/source/patterns/components/card/card.twig.
Read public_html/project/public/seanmontague.html (the .map-links section).

Create a new component: map-link-list
A bordered list of links with arrow indicators and border-left hover
accent. Used in map sections and sidebars.

Create these files:
  source/patterns/components/map-link-list/map-link-list.twig
  source/patterns/components/map-link-list/map-link-list.css
  source/patterns/components/map-link-list/map-link-list.stories.jsx
  source/patterns/components/map-link-list/map-link-list.yml

map-link-list.twig:
  {{ attach_library('surface/map-link-list') }}
  <div class="map-link-list{{ modifier ? ' ' ~ modifier }}">
    {% for item in items %}
      <a href="{{ item.url|default('#') }}"
         class="map-link-list__item"
         {{ item.external ? 'target="_blank" rel="noopener"' }}>
        <span class="map-link-list__label">{{ item.label }}</span>
        <span class="map-link-list__arrow" aria-hidden="true">→</span>
      </a>
    {% endfor %}
  </div>

map-link-list.css — BEM, uses design tokens:
  .map-link-list {
    border: 1px solid var(--border);
  }

  .map-link-list__item {
    align-items: center;
    border-bottom: 1px solid var(--border);
    border-left: 3px solid transparent;
    color: var(--text);
    display: flex;
    font-family: var(--font-mono-nek);
    font-size: 11px;
    justify-content: space-between;
    letter-spacing: 0.07em;
    padding: 13px 18px;
    text-decoration: none;
    transition: background 0.2s ease, border-left-color 0.2s ease;
  }

  .map-link-list__item:last-child { border-bottom: none; }

  .map-link-list__item:hover {
    background: var(--bg);
    border-left-color: var(--forest);
  }

  .map-link-list__arrow {
    color: var(--muted);
    transition: transform 0.2s ease;
  }

  .map-link-list__item:hover .map-link-list__arrow {
    transform: translateX(3px);
  }

map-link-list.yml (Storybook fixture):
  items:
    - label: 'Kingdom Trails Network'
      url: '#'
    - label: 'Burke Mountain Ski Lines'
      url: '#'
    - label: 'NEK Touring Routes'
      url: '#'
    - label: 'Burke Area Trails'
      url: '#'
    - label: 'Download GPX'
      url: '#'
      external: true

Add library entry to surface.libraries.yml:
  map-link-list:
    css:
      component:
        dist/css/map-link-list.css: {}

Show all new files before writing.
After: lando npm run build
Verify in Storybook: map-link-list renders with arrow hover animation.
```

---

## Step E — Trip page template

```
Read public_html/themes/custom/surface/CLAUDE.md.
Read the root CLAUDE.md.
Read public_html/themes/custom/surface/source/patterns/components/map/map.twig.
Read public_html/themes/custom/surface/source/patterns/components/map/map.js.
Read public_html/themes/custom/surface/source/patterns/components/places-card/places-card.twig.
Read public_html/themes/custom/surface/source/patterns/collections/article/article.css.
Read public_html/themes/custom/surface/templates/content/node--trip.html.twig.
Read public_html/project/public/ireland-2024.html.

Redesign node--trip.html.twig using ireland-2024.html as the layout
reference. Create a matching trip.css for the new layout classes.
All CSS must use BEM and Surface design tokens — no hardcoded hex values.

The ireland-2024.html layout (top to bottom):

1. HERO IMAGE + HEADER
   Full-width image (schema_image) at 68vh, then below it:
   eyebrow label ("Travel · Ireland"), Bebas Neue title, DM Mono
   date string. Classes: .trip__hero-image-wrap, .trip__hero-content,
   .trip__hero-label, .trip__hero-title, .trip__hero-dates.

2. DESTINATION STRIP
   Horizontally scrollable row of Place names separated by · from
   schema_destination. Each name links to #destination-{delta}.
   Classes: .trip__destination-strip, .trip__destination-strip-inner,
   .trip__destination-name, .trip__destination-sep.

3. TRIP STATS BAR
   4-column grid. Pull values from Twig if available, otherwise
   calculate or hardcode empty. Stats:
     Duration — compute from field_departure_date / field_arrival_date
     Places   — schema_destination|length
     Distance — schema_distance.value if set (optional)
     Counties — leave empty (no field for this)
   Classes: .trip__stats, .trip__stat, .trip__stat-label,
   .trip__stat-value, .trip__stat-unit.

4. ROUTE MAP
   Full-width Leaflet map with:
   - Numbered markers for each Place in schema_destination
   - Dashed polyline connecting them in order
   - OSM/OpenTopoMap tiles (not USGS — Ireland content)
   
   Use existing map.twig for the map container. The numbered markers
   and polyline must be initialized via a new JS IIFE in a <script>
   block after the map container, using the surface-map-ready event:
   
   document.addEventListener("surface-map-ready", function(e) {
     if (e.detail.map_id !== "trip-map-{{ node.id }}") return;
     var map = e.detail.map;
     // Add polyline and numbered markers using destinations array
   });
   
   Pass markers array to map.twig but also add polyline + numbered
   pins via the event. The destinations data (lat/lon/name/num) must
   be output as a JSON array in a data attribute or inline script var.

   Map header bar above the map (surface2 background):
   Classes: .trip__map-section, .trip__map-header,
   .trip__map-header-label, .trip__map-header-note.

5. BODY + ITINERARY SIDEBAR
   Two-column layout: narrative left (1fr), sidebar right (320px).
   Use existing article-wrap / article__body / article__sidebar classes.
   
   Left: {{ content.field_body|field_value }}
   
   Right sidebar — itinerary list from schema_destination (ordered
   Places with number, name, address region, date if available).
   Use .sidebar-card > .sidebar-card__header + .sidebar-card__body.
   Inside: numbered list with .trip__itinerary-list,
   .trip__itinerary-item (grid: 28px num + 1fr body),
   .trip__itinerary-num, .trip__itinerary-place, .trip__itinerary-meta.

6. DESTINATION CARDS GRID
   4-column grid, one card per schema_destination Place.
   Each card has:
     - Mini Leaflet map (160px tall) — separate map instance per card
     - Card body: sequential number, place name (Bebas Neue), region
     - Hover: background changes to --bg, arrow appears top-right
   
   Mini maps init via surface-map-ready on each card map_id.
   Each card map is non-interactive (no zoom/drag/scroll).
   
   Classes: .trip__cards-section, .trip__cards-section-header,
   .trip__cards-title, .trip__cards-count, .trip__cards-grid,
   .trip__destination-card, .trip__card-map, .trip__card-body,
   .trip__card-num, .trip__card-name, .trip__card-region,
   .trip__card-arrow.

TWIG DATA TO EXTRACT:
  {% set depart = node.field_departure_date.value %}
  {% set arrive = node.field_arrival_date.value %}
  {% if depart and arrive %}
    {% set date_display = depart|date("F j") ~ "–" ~ arrive|date("F j, Y") %}
  {% elseif depart %}
    {% set date_display = depart|date("F j, Y") %}
  {% else %}
    {% set date_display = "" %}
  {% endif %}
  
  {% set destinations = [] %}
  {% for item in node.schema_destination %}
    {% set place = item.entity %}
    {% if place %}
      {% set destinations = destinations|merge([{
        "delta":  loop.index,
        "id":     "destination-" ~ loop.index,
        "name":   place.label,
        "lat":    place.schema_latitude.value,
        "lon":    place.schema_longitude.value,
        "region": place.schema_address.administrative_area ?? "",
        "country": place.schema_address.country_code ?? "IE",
      }]) %}
    {% endif %}
  {% endfor %}

TILE OVERRIDE:
  Read node.field_map_tiles.value — if set use that tile key,
  otherwise default to "open-topo" for trip pages (not usgs-topo
  which only covers the US).
  Pass as tiles parameter to map.twig.

CREATE THESE FILES:
  public_html/themes/custom/surface/templates/content/node--trip.html.twig
  public_html/themes/custom/surface/source/patterns/collections/trip/trip.css
  
Add trip.css library entry to surface.libraries.yml:
  surface-trip:
    css:
      component:
        dist/css/trip.css: {}

Add {{ attach_library("surface/surface-trip") }} to the template.

ALSO CREATE Storybook page demo:
  source/patterns/pages/trip/trip.twig
  source/patterns/pages/trip/trip.stories.jsx
  source/patterns/pages/trip/trip.yml

Use realistic Ireland April 2024 data in the yml fixture with all
12 destinations and their coordinates from ireland-2024.html.

Show all new/changed files before writing.
After: lando npm run build
Verify in Storybook: Pages → Trip shows the full layout.
Then verify live at https://seanmontague.lndo.site/trips/ireland-april-2024
```

---

## Execution order

A → B → C → D → E

Each step builds on the previous. Verify in Storybook after each
before proceeding to the next.
