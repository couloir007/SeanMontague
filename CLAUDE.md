# SeanMontague.com — Project Root

## Project Documentation
- **`.junie/guidelines.md`**: Master operational guide (build, test, Lando) for Junie.
- **`public_html/themes/custom/surface/CLAUDE.md`**: Detailed theme architecture, Twig rules, and content model.

## Project Instructions & Standards

### AI Instructions
- **Consult Documentation**: Always start by checking `.junie/guidelines.md` for build/test commands and `public_html/themes/custom/surface/CLAUDE.md` for theme patterns.
- **Environment**: All PHP/Drupal commands must be prefixed with `lando` (e.g., `lando drush`, `lando composer`).
- **Tests**: When adding new functionality, add unit tests in `public_html/modules/custom/*/tests/` and verify using `lando phpunit` or standalone scripts as described in the guidelines.

### Coding Standards
- **Drupal**: Follow [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards) for PHP and YAML.
- **CSS**: Use BEM (Block Element Modifier) naming convention.
- **JavaScript**: Use ES6+ standards.
- **Theme Architecture**: Follow the modified Atomic Design hierarchy (Base, Elements, Components, Collections, Layouts, Pages).
- **Templates**: Use Twig namespaces (`@components`, `@elements`, etc.) for all includes.

## Directory Structure
- Web root: `public_html/`
- Custom theme: `public_html/themes/custom/surface/`
- Custom modules: `public_html/modules/custom/`
- Config sync: `config/sync/`
- Composer: `composer.json` at project root

## Environment
- Local: Lando (Pantheon recipe)
- Always use `lando drush`, `lando composer`, `lando terminus`

## Active Development
- Theme: `public_html/themes/custom/surface/`
- See `public_html/themes/custom/surface/CLAUDE.md` for
  full theme context
-
