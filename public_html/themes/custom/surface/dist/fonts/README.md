# Fonts

Surface uses three Google Fonts, loaded from CDN at the top of `colors_and_type.css`:

- **Bebas Neue** — display (hero, titles, big numbers). Weight 400 only.
- **Cormorant Garamond** — body + serif heads. Weights 300, 400, 600 + italic 300/400.
- **DM Mono** — labels, nav, metadata. Weights 300, 400.

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
```

## Self-hosting

If you need offline/self-hosted fonts, download TTF/WOFF2 from:
- https://fonts.google.com/specimen/Bebas+Neue
- https://fonts.google.com/specimen/Cormorant+Garamond
- https://fonts.google.com/specimen/DM+Mono

Drop them here and replace the `@import url(...)` line in `colors_and_type.css` with `@font-face` declarations pointing at local paths.

## Substitutions (not in use — nearest fallbacks if a font fails to load)

- Bebas Neue → Oswald, Impact
- Cormorant Garamond → EB Garamond, Georgia
- DM Mono → IBM Plex Mono, Menlo
