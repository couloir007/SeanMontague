# Agent Guardrails — seanmontague.com

Standing rules for any AI agent (Junie, Claude Code) working in this repo. Point
every task prompt at this file, then add the task-specific details. These are
the rules that are expensive to get wrong; obey them over any inferred default.

## Git & safety — never violated
- **Sean does ALL git.** Never commit, push, stage, or create/switch branches.
- Finish by **showing the diff** and a short summary of what changed. Stop there.
- Never hand-edit anything under **`dist/`**. It is the committed Vite build
  output (Pantheon has no build step), regenerated from `source/` — editing it
  directly is overwritten on the next build and ships broken assets.

## Build & commands
- **`npm` runs on the HOST**, not Lando — the appserver is PHP-only, so
  `lando npm` does not exist. Build: `npm run build` (from the theme dir).
- Prefix **Drupal/PHP** commands with `lando`: `lando drush …`,
  `lando composer …`, `lando terminus …`, `lando php …`, `lando phpunit …`.
- PHP/Twig-only changes need **no build** — just `lando drush cr`. Only a
  change under `source/` (CSS/JS) needs `npm run build` + committing `dist/`.
- Config: export UI changes with `lando drush cex` and commit them with the
  feature that needs them.

## Verified field accessors (the codebase gets these wrong constantly)
- **Geofield:** `entity.schema_geo.lat` / `.lon` (Twig) or
  `$e->get('schema_geo')->lat` / `->lon` (PHP) return the float **directly**.
  `.lat.value` / `->lat->value` and `.0.value.lat` are **NULL**.
- **Entity label:** use `$e->label()` (PHP). `entity.label.value` /
  `->label->value` returns **empty** — `label` is a method, not a field.
  (Node `title` IS a real field, so `node.title.value` is fine; entity `label`
  is not.)

## Theme conventions (Surface)
- **Node templates are pure markup.** Data is shaped in bundle/view-mode
  preprocess hooks in `includes/node.theme`
  (`surface_preprocess_node__{suggestion}()`); there is **no base
  `surface_preprocess_node()`**. Each hook fires because its
  `node--*.html.twig` template registers the theme hook — add a new one by
  creating the template, then the matching hook. Don't reintroduce a base hook.
- **Flat BEM**, no theme prefix on classes: `.map`, `.trip__header`,
  `.nav--light`. The `surface/` prefix appears only in library names
  (`attach_library('surface/map')`), never in CSS class names.
- **Flat Twig namespaces:** `@components/card.twig`, `@collections/trip/…`.
  Never `@components/surface/…` (the old nested path is gone).
- Component variant CSS lives **with the component** (`name.css`), never in a
  layout/collection stylesheet.
- JS files begin with `/* jshint esversion: 6 */` (map.js uses `11`). Never use
  `once()` — use a data-attribute/property guard (Storybook has no `once`).
- Atomic-design include rules: elements include nothing; components → elements;
  collections → components + elements; layouts → anything.

## When a doc and the code disagree
The live code wins. If an existing template models a bug (e.g. `field_body`,
`node.label.value`), the prompt's stated correct version wins over what the file
shows — don't preserve the bug.

## Read first
`.junie/guidelines.md` (build/test/ops) and the relevant `CLAUDE.md` — root for
structure, `themes/custom/surface/CLAUDE.md` for theme/preprocess/content model.
For map/geo work also `modules/custom/MODULES-trail_mapper-vs-seanmontague_map.md`.
