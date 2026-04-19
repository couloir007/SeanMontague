# Surface Pattern Library — UI Kit 2

A Storybook-style viewer showing the Drupal pattern library at `source/patterns/` in isolation. These are the **implementation** components that back the editorial site — useful when designing new Surface components before building them in Storybook.

Each component is recreated with the pattern library's **actual BEM class names** (`.card`, `.hero`, `.stats-bar`, `.places-card`, etc.), so styles lifted from here map 1:1 to the Twig/CSS in `source/patterns/`.

## Files
- `index.html` — the pattern browser. Left rail is the pattern hierarchy (Elements → Components → Collections → Layouts → Pages); right pane shows the selected specimen.
- `surface.css` — the compiled design-system stylesheet (all `.card`, `.hero`, `.nav`, etc. class definitions, following the source exactly).
- `specimens.jsx` — one React component per pattern, using real Surface class names.

## Relationship to Kit 1

| | Kit 1 — `seanmontague/` | Kit 2 — `surface/` (this kit) |
|---|---|---|
| Role | Design **intent** | **Implementation** reference |
| Namespace | `sm-*` scoped classes | Canonical BEM (`.card`, `.hero`, …) |
| Source | `public/seanmontague.html` + article | `source/patterns/**` (Twig + CSS) |
| Use it to | Design new page compositions, trip layouts, article templates | Design new Surface components before building them in Storybook |

The two kits are **converging but not identical** — the Bolt HTML files push design direction a bit further than the pattern library has caught up to yet.
