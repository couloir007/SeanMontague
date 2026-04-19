---
name: surface-design
description: Use this skill to generate well-branded interfaces and assets for Surface — the design system for Sean Montague's editorial personal site (Kingdom Trails mountain biking, Burke Mountain skiing, permaculture, and Leaflet mapping). Typography-forward outdoor / editorial aesthetic. Use for production or for throwaway prototypes, slides, and mocks.
user-invocable: true
---

Read the `README.md` file within this skill, then explore the other available files:

- `README.md` — brand overview, content voice, visual foundations, iconography
- `colors_and_type.css` — copy this into any artifact you build; all tokens live here
- `fonts/` — self-hosted Bebas Neue + Cormorant Garamond (DM Mono is on Google Fonts)
- `assets/` — logos, bitmap assets, reference images
- `preview/` — small HTML cards showing each design system element in isolation
- `ui_kits/seanmontague/` — React (JSX + Babel) recreation of the editorial site with components for Nav, Hero, ArticleGrid, PlaceGrid, AboutSection, ContactSection, Footer, StatsBar, Blockquote, ArticleView. Lift components wholesale.
- `ui_kits/surface/` — pattern-library browser showing every Surface element and component on one page (`surface.css` has the BEM classes mirrored from the Drupal `source/patterns/` tree). Use this as the primitives reference when composing new screens.

## The rules, in short

1. **No rounded corners anywhere.** `border-radius: 0` is the default. This is non-negotiable.
2. **No drop shadows. No gradients.** Depth comes from hairline borders and the warm/dark value shift between `--bg` (#f7f6f2) and `--bright` (#1a1a1a).
3. **Three typefaces, three jobs.** Bebas Neue (display, big numerals, hero), Cormorant Garamond (body, article titles, italic accents), DM Mono (labels, wordmark, nav, eyebrows — almost always uppercase with 0.16em–0.28em letter-spacing).
4. **The hover rail is the signature.** Interactive surfaces (cards, links, nav items) get a 3px `border-left: 3px solid transparent` that transitions to `var(--forest)` on hover, often with a background shift to `var(--surface)` or `var(--bg)`.
5. **Pillar colors are categorical.** `--forest #3a5a40` = permaculture + primary accent, `--trail #7a3410` = MTB + skiing, `--sky #4a7c9e` = maps + Leaflet. Never mix them decoratively.
6. **Voice is plainspoken and first-person.** "I've been riding Kingdom Trails for a decade." Lowercase tags, title-case section labels in DM Mono caps. No emoji, no buzzwords.

## When building visual artifacts (slides, mocks, throwaway prototypes)

Copy `colors_and_type.css`, `fonts/`, and any needed assets out into your artifact folder. Write static HTML. For React prototypes use the inline JSX + Babel pattern from `ui_kits/seanmontague/index.html` — link Leaflet from CDN for maps, keep OpenTopoMap tiles with `filter: saturate(0.75) contrast(0.92)` for the signature muted cartography look.

## When working on production code

Copy the tokens, read the voice guide, and design with the same restraint — hairlines, uppercase mono labels, serif body, Bebas display. The `ui_kits/seanmontague/` components are cosmetic-only reference implementations; rebuild with your real framework but keep the spacing / type / hover behavior identical.

## If the user invokes this skill without guidance

Ask them what they want to build (landing page, slide deck, article layout, UI mock?), ask 3–5 clarifying questions (audience, content pillar, whether they want variations), then act as the expert designer and output HTML artifacts or production code as appropriate.
