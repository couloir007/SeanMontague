# Project Development Guidelines

This project is a Drupal-based site (`seanmontague`) using Lando for local
development and a custom theme (`surface`) built with Vite and Storybook.

## Related Documentation
This project uses multiple documentation files for AI context:
- **`.junie/guidelines.md` (this file)**: Primary operational guide for Junie. Build commands, testing, high-level project rules.
- **`AGENT-GUARDRAILS.md` (root)**: Standing rules for any AI agent (git, dist/, verified accessors, conventions). Read before any task.
- **`CLAUDE.md` (root)**: High-level repository structure overview.
- **`public_html/themes/custom/surface/CLAUDE.md`**: Deep-dive technical reference for the `surface` theme — Twig rules, preprocess pattern, content model, Leaflet. Refer to this for any frontend or schema-related work.
- **`public_html/modules/custom/MODULES-trail_mapper-vs-seanmontague_map.md`**: which map/geo module owns what.

## Build and Configuration

### Local Environment (Lando)
The project uses Lando. To start the local environment:
```bash
# Start Lando services
lando start

# View Lando info (URLs, database credentials)
lando info
```

Local URL: `https://seanmontague.lndo.site`.

### Dependency Management
- **PHP**: Managed by Composer. The Drupal root is located in `public_html/`.
  ```bash
  lando composer install
  ```
- **Frontend (Theme)**: The `surface` theme is at `public_html/themes/custom/surface/`.
  **`npm` runs on the HOST** — the Lando appserver is PHP-only, so there is no
  `lando npm`. Do not prefix npm commands with `lando`.
  ```bash
  cd public_html/themes/custom/surface/
  npm install
  npm run build   # Full production build → dist/ (commit dist/; Pantheon has no build step)
  npm run watch   # Development mode (Vite + Storybook)
  ```

### Key Directories
- `public_html/`: Drupal web root.
- `public_html/modules/custom/`: Custom modules — `trail_mapper` (GeoJSON/elevation engine), `seanmontague_map` (site map aggregation), `seanmontague_schemadotorg` (JSON-LD), `trip_import`, `map_page`, `external_pg`, `trailmapper_safeguards`.
- `public_html/themes/custom/surface/`: Primary custom theme.
- `config/`: Drupal configuration exports (`lando drush cex` / `cim`).

---

## Testing Information

### PHP Testing
PHPUnit is the standard; tests live in each custom module under `tests/`. PHP
runs in the Lando appserver, so always invoke it through `lando`.

The existing reference test:
```bash
lando phpunit public_html/modules/custom/trail_mapper/tests/Unit/GeoElevationCalculatorTest.php
```

Run a module's tests, or all custom-module tests:
```bash
lando php vendor/bin/phpunit public_html/modules/custom/trail_mapper
lando php vendor/bin/phpunit public_html/modules/custom
```

#### Adding a new test
1. Add the class under the module's `src/` (e.g. `src/Service/Foo.php`).
2. Add a corresponding test under the module's `tests/Unit/` (e.g. `tests/Unit/FooTest.php`),
   following `GeoElevationCalculatorTest` as the pattern.
3. Run it with `lando phpunit <path>`.

### Frontend Testing
Storybook is used for visual testing and component development. Run
`npm run watch` in the theme directory and open the Storybook URL it prints
(serves on `localhost:6006` by default — confirm against your `.storybook`
config if it differs).

---

## Additional Development Information

### Theme Architecture (Surface)
- **Methodology**: Modified Atomic Design.
  - `@base`: Global styles.
  - `@elements`: Smallest units (no inclusions).
  - `@components`: May include elements.
  - `@collections`: Composed of components and elements.
  - `@layouts`: Drupal region wrappers.
  - `@pages`: Full page Storybook demos.
- **Node templates are pure markup** — data is shaped in
  `surface_preprocess_node__{suggestion}()` hooks in `includes/node.theme`
  (no base `surface_preprocess_node()`). See the theme CLAUDE.md.
- **Tooling**: Vite for bundling, Twig for templating, flat BEM for CSS naming,
  flat Twig namespaces (never `@components/surface/…`).

### Database Integration
The `external_pg` module provides a service to connect to an external
PostgreSQL database. Connection details are currently defined in
`ExternalPgService.php` but should ideally be moved to `settings.php`.

### Code Style
- Follow [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards).
- CSS: flat BEM in the `surface` theme — no `surface-` prefix on class names.
- JavaScript: ES6+; every JS file begins with `/* jshint esversion: 6 */`.
- Verified field accessors (see `AGENT-GUARDRAILS.md`): geofield `->lat`/`->lon`
  direct (no `->value`); entity label `->label()` (never `->label->value`).

---

## Accessibility Resources
For WCAG compliance and standard accessibility checks:
- [Axe Rule: Color Contrast](https://dequeuniversity.com/rules/axe/4.11/color-contrast?application=axeAPI)
- [Axe Rule: Link in Text Block](https://dequeuniversity.com/rules/axe/4.11/link-in-text-block?application=axeAPI)
