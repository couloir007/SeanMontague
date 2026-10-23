# Learnings and gotchas: SeanMontague.com build

Project-level gotchas the repo docs don't cover. Code, theme, and Blueprints gotchas belong in the repo docs (root `CLAUDE.md`, the Surface `CLAUDE.md` files, `SCHEMADOTORG.md`, `STORYBOOK.md`), where Claude Code also reads them. Add an entry here only when it fits nowhere in the repo: environment, hosting, dependency history, doc drift, open security items.

One bullet per learning: what happened, the rule, and the date or source it was checked against.

Lives in `docs/`; the Claude project copy is refreshed from it. Entries marked **[GAP]** need a fact the attachments don't supply.

## Tooling
- Junie retired 2026-10-22; Claude Code in PhpStorm is the only coding agent. `.junie/guidelines.md` was already stale: it named the project `roundybrookfarm`, ran `composer` and `npm` without `lando`, and scaffolded tests as a plain PHP script in a root `tests/Unit/` directory. Rule: delete `.junie/` from the repo; if any copy survives, root `CLAUDE.md` wins.
- The repo `README.md` is one heading, `# roundybrookfarm`, and carries no project information.

## Security and config
- `external_pg` credentials (`external_pg` is deprecated; see decisions.md): verified hardcoded in `ExternalPgService.php` (2026-10-22 module snapshot), with a private LAN host, and the constructor echoes a connection message. Rule: credentials belong in `settings.local.php` locally (root `CLAUDE.md`: never edit `settings.php` directly) and never in code or knowledge docs. The password has been in the repo, so rotate it if it's used anywhere else. Production is AcuGIS, which can't reach a LAN host, so `external_pg` can't work in production; it's deprecated in favor of the TrailMapper API. Until it's deleted, any credentials belong in the server's `settings.local.php`, outside the repo.

## Database
- Raw inserts of uid 0 need NO_AUTO_VALUE_ON_ZERO in the session SQL mode, or MariaDB auto-numbers it.
- MariaDB's default collation is case-insensitive; data from PostgreSQL can collide on unique keys that differ only by case.
- The Lando MariaDB client requires SSL by default; the server has none. `drush sql:*` fails with "SSL is required, but the server does not support it". Drush runs the client with `--defaults-file`, so global client config in `/etc/mysql` is ignored. Pass the flag per command: `lando drush sql:query "..." --extra=--skip-ssl`, `lando drush sql:dump --extra-dump=--skip-ssl --result-file=...` (verified 2026-10-23). No persistent local-only setting was found (drush.yml and `DRUSH_OPTIONS_*` env vars did not take effect); don't commit `--skip-ssl` to shared drush config without checking the server's client (2026-10-23).

## Hosting
- AcuGIS uses CloudLinux PHP Selector. Web PHP is set per directory, and the CLI default differs, so run Drush and Composer on the server through `/opt/alt/php84/usr/bin/php`.

## Accessibility references
- axe color contrast: https://dequeuniversity.com/rules/axe/4.11/color-contrast?application=axeAPI
- axe link in text block: https://dequeuniversity.com/rules/axe/4.11/link-in-text-block?application=axeAPI

## Not captured yet
- **[GAP]** Blueprints and JSON-LD, Composer and upgrade history, hosting and DNS, and Drupal theming learnings. The attachments hold none beyond what the two `CLAUDE.md` files already state. Check `SCHEMADOTORG.md`, theme-root `CLAUDE.md`, and the `seanmontague_schemadotorg` README for overlap before adding any.
