# Fix: Hero background map not rendering in Storybook

## Root cause

`map.js` initialised via `setTimeout(() => initAll(), 0)`. At that point in the
hero story, Leaflet (`L`) had not yet loaded — it comes in asynchronously via
`preview-head.html`. `initMap()` hit the `typeof L === 'undefined'` guard and
returned silently, with `el._surfaceMapInit = true` already set. When the
Drupal behavior later tried to reinitialise, the flag blocked it.

Map component stories appeared to work because their render timing happened to
allow Leaflet to load first. The hero story — rendered with `layout: 'fullscreen'`
— exposed the race consistently.

## Fix (`map.js`)

Replaced the one-shot `setTimeout(() => initAll(), 0)` with a polling loop that
waits for `L` before calling `initAll()`:

```javascript
// Before
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initAll());
} else {
  setTimeout(() => initAll(), 0);
}

// After
(function waitForLeaflet(attempts) {
  if (typeof L !== 'undefined') { initAll(); return; }
  if (attempts > 20) { console.warn('map: Leaflet did not load after 2s'); return; }
  setTimeout(() => waitForLeaflet(attempts + 1), 100);
}(0));
```

Polls every 100 ms, up to 20 attempts (2 s). The Drupal path
(`Drupal.behaviors`) is unaffected — Drupal guarantees Leaflet is loaded
before behaviors run.

## Also fixed (previous turn)

`HeroBackground` story in `map.stories.jsx` was missing `modifier: 'map--standalone'`.
Without it, `.map { height: 100% }` resolved to 0 in the Storybook story wrapper.
Added the modifier so the story has an explicit 440 px height, matching all
other standalone map stories.
