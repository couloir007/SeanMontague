# Persistent Audio Player

## Status: Planned — not yet implemented

Reference: `tal_episode` Drupal 7 module (uploaded). TAL uses a
`/playlist/episode/%` JSON endpoint + client-side navigation to keep
audio playing across page loads.

---

## The core problem

A standard Drupal page navigation destroys the DOM — including any `<audio>`
element — and stops playback. The solution is to prevent full page reloads
during navigation, keeping the player element alive in the DOM.

---

## Approach: Turbo Drive + permanent player element

**Turbo Drive** (Hotwire) intercepts link clicks, fetches the new page via
fetch(), and swaps only the `<body>` content. Elements marked
`data-turbo-permanent` are kept across navigations — never replaced, never
destroyed. The `<audio>` element inside the player keeps playing.

This is the cleanest Drupal 10 solution and has an existing integration
module. TAL achieves the same result via a custom SPA shell in D7.

### Drupal module

```bash
lando composer require drupal/turbo
lando drush en turbo
```

The `drupal/turbo` module wires Turbo into Drupal's AJAX system, BigPipe,
and asset loading. No custom routing changes needed.

### page.html.twig change

```twig
{# Outside the Turbo drive swap zone — survives navigation #}
<div id="surface-player" data-turbo-permanent>
  {% include '@components/player/player.twig' %}
</div>
```

---

## Architecture

### Player component — new

```
source/patterns/components/player/
├── player.twig
├── player.css
└── player.js
```

The player lives in the site layout, not inside any node template. It
listens for a custom `surface-play` event dispatched by any template that
has playable audio.

### Event API — decoupled trigger

Any template, component, or JS on the page fires:

```js
window.dispatchEvent(new CustomEvent('surface-play', {
  detail: {
    url:      'https://audio.example.com/episode.mp3',
    title:    'Ireland 2024',
    subtitle: 'April 19–27',
    image:    '/path/to/image.jpg',
    nodeId:   42,
  }
}));
```

`player.js` catches the event, loads the URL into the `<audio>` element,
and starts playback. The player has no knowledge of content types — it only
handles audio state.

### Audio data endpoint — new

A lightweight JSON controller returns audio metadata for a given node.
The player or a play button fetches this before dispatching `surface-play`.

```
GET /api/audio/{node}

Response:
{
  "url":      "https://...",
  "title":    "Ireland 2024",
  "subtitle": "April 19–27",
  "image":    "https://...",
  "duration": 3600,
  "transcript": "..."
}
```

Source: `node.schema_audio.entity` (media:AudioObject) — the field already
exists on the content model. The endpoint traverses:

```
node.schema_audio
  → media:AudioObject
    → schema:contentUrl (file or external URL)  →  url
    → schema:duration                            →  duration
    → schema:transcript                          →  transcript
node.schema_image
  → media:ImageObject → file URI                →  image
node.label                                       →  title
```

Module location: new `surface_player` module in
`public_html/modules/custom/surface_player/`, or added to
`seanmontague_schemadotorg` if kept minimal.

---

## Content model (existing — no changes needed)

The `schema_audio` field (entity_reference → media:AudioObject) is already
defined on the content model for article, trail_report, tourist_trip, and
the podcast bundles. The AudioObject media type carries:

| Schema.org property | Drupal field | Notes |
|---|---|---|
| `schema:contentUrl` | file or external URL | MP3 source |
| `schema:duration` | duration field | Audio length |
| `schema:transcript` | text field | Source text |
| `schema:encodingFormat` | string | `audio/mpeg` |
| `schema:isPartOf` | entity ref → node | Post it belongs to |

ElevenLabs generated audio attaches via this same field.

---

## Player component detail

### player.twig

```twig
{{ attach_library('surface/player') }}
<div class="player" id="surface-player" data-turbo-permanent aria-label="Audio player">
  <div class="player__meta">
    <div class="player__image">
      <img class="player__img" src="" alt="" aria-hidden="true">
    </div>
    <div class="player__info">
      <div class="player__title"></div>
      <div class="player__subtitle"></div>
    </div>
  </div>
  <div class="player__controls">
    <button class="player__btn player__btn--back"
            aria-label="Back 15 seconds">
      <svg><!-- back 15 icon --></svg>
    </button>
    <button class="player__btn player__btn--play" aria-label="Play">
      <svg class="player__icon-play"><!-- play --></svg>
      <svg class="player__icon-pause"><!-- pause --></svg>
    </button>
    <button class="player__btn player__btn--forward"
            aria-label="Forward 30 seconds">
      <svg><!-- forward 30 icon --></svg>
    </button>
  </div>
  <div class="player__progress">
    <span class="player__time player__time--current">0:00</span>
    <input class="player__scrubber" type="range" min="0" max="100"
           value="0" aria-label="Seek">
    <span class="player__time player__time--duration">0:00</span>
  </div>
  <audio class="player__audio" preload="metadata"></audio>
</div>
```

### player.js responsibilities

- Listen for `surface-play` custom event → load URL → play
- Play / pause toggle
- Back 15s / forward 30s (standard podcast controls)
- Scrubber seek
- Update title / subtitle / image from event detail
- Persist playback position to `sessionStorage` keyed by nodeId
- Resume position on re-trigger of same nodeId
- Dispatch `surface-player-state` events for any external UI to sync

### player.css

- Fixed bottom bar, full width, `z-index` above content
- Hidden (`opacity: 0`, `pointer-events: none`) until first play event
- Bebas Neue for title, DM Mono for timestamps
- Colors from design tokens — `--forest`, `--bright`, `--surface`, `--bg`
- Progress bar accent: `--forest`
- `data-turbo-permanent` element must not use CSS transitions on
  properties that Turbo might animate during navigation

---

## Play button in templates

Node templates that have `schema_audio` render a play button that fetches
the endpoint and fires the event:

```twig
{# In node--article.html.twig, node--tourist-trip.html.twig, etc. #}
{% if node.schema_audio.entity %}
  <button class="surface-play-btn"
          data-node-id="{{ node.id }}"
          data-audio-endpoint="/api/audio/{{ node.id }}"
          aria-label="Play audio for {{ node.label }}">
    <svg><!-- play icon --></svg>
    Listen
  </button>
{% endif %}
```

The button's JS (in a small `play-btn.js` or inline) fetches the endpoint
and dispatches `surface-play`. The player component handles the rest.

---

## TAL D7 reference

The D7 `tal_episode` module's relevant patterns:

| D7 pattern | D10 equivalent |
|---|---|
| `/playlist/episode/%` JSON endpoint | `/api/audio/{node}` controller |
| Custom JS SPA shell for navigation | `drupal/turbo` + `data-turbo-permanent` |
| `field_audio_archive` file field | `schema_audio` → media:AudioObject |
| Episode queue / Megaphone integration | ElevenLabs → AudioObject (planned) |

The queue and Megaphone integrations in the D7 module are TAL-specific
production infrastructure — not relevant here.

---

## surface.libraries.yml additions

```yaml
player:
  css:
    component:
      dist/css/player.css: {}
  js:
    dist/js/player.js: {}
  dependencies:
    - core/drupal

play-btn:
  js:
    dist/js/play-btn.js: {}
  dependencies:
    - surface/player
```

---

## Storybook story

```
source/patterns/components/player/player.stories.jsx
```

Story: `Components/Player`

Fixture: static audio URL (public domain recording). Stories for:
- Default (idle, no audio loaded)
- Playing state
- Paused state

---

## Accessibility

- `<audio>` element is visually hidden but in DOM — screen readers use
  browser native controls as fallback
- All controls have `aria-label`
- Scrubber is a native `<input type="range">` — keyboard navigable
- Player announces title change via `aria-live="polite"` on `.player__title`
- `prefers-reduced-motion`: disable progress bar animation

---

## Pending

- [ ] `lando composer require drupal/turbo` + enable + test with existing theme
- [ ] Audit existing JS for Turbo compatibility (IIFE maps/elevation — should be fine)
- [ ] `data-turbo-permanent` on player wrapper in `page.html.twig`
- [ ] Create `surface_player` module with `/api/audio/{node}` controller
- [ ] Build `@components/player/player.twig` + `player.css` + `player.js`
- [ ] Build `play-btn.js` — fetch endpoint + dispatch `surface-play`
- [ ] Add play button to `node--article.html.twig` (conditional on `schema_audio`)
- [ ] Add play button to `node--tourist-trip.html.twig`
- [ ] Add `player` and `play-btn` libraries to `surface.libraries.yml`
- [ ] Storybook story
- [ ] Test Turbo + Leaflet map interaction (map reinit on navigation)
- [ ] Test Turbo + Drupal AJAX (forms, facets)
- [ ] Populate `schema_audio` on Ireland 2024 articles via ElevenLabs
