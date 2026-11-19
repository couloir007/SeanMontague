# Learnings and gotchas: SeanMontague.com build

Project-level gotchas the repo docs don't cover. Code, theme, and Blueprints gotchas belong in the repo docs (root `CLAUDE.md`, the Surface `CLAUDE.md` files, `SCHEMADOTORG.md`, `STORYBOOK.md`), where Claude Code also reads them. Add an entry here only when it fits nowhere in the repo: environment, hosting, dependency history, doc drift, open security items.

One bullet per learning: what happened, the rule, and the date or source it was checked against.

Lives in `docs/`; the Claude project copy is refreshed from it. Entries marked **[GAP]** need a fact the attachments don't supply.

## Tooling
- Local dev moved from Lando to DDEV (2026-11-19). DDEV's router and Lando's proxy both bind ports 80/443, so only one can run (`ddev poweroff` / `lando poweroff`). The database host under DDEV is `db` (`settings.ddev.php`); the Lando-era hostnames `mariadb` and `database` no longer apply. Lando's `db-export` defaulted to the Postgres service and produced a Postgres dump by mistake (2026-11-19).
- PhpStorm safe-write can leave root-owned files such as `composer.lock~<hash>.tmp`; delete with `ddev exec rm -f` or `sudo`. `*.tmp` should be gitignored (not yet in `.gitignore`; Sean to add) (2026-11-19).
- Junie retired 2026-10-22; Claude Code in PhpStorm is the only coding agent. `.junie/guidelines.md` was already stale: it named the project `roundybrookfarm`, ran `composer` and `npm` without `lando`, and scaffolded tests as a plain PHP script in a root `tests/Unit/` directory. Rule: delete `.junie/` from the repo; if any copy survives, root `CLAUDE.md` wins.
- The repo `README.md` is one heading, `# roundybrookfarm`, and carries no project information.

## Security and config
- `external_pg` credentials (`external_pg` is deprecated; see decisions.md): verified hardcoded in `ExternalPgService.php` (2026-10-22 module snapshot), with a private LAN host, and the constructor echoes a connection message. Rule: credentials belong in `settings.local.php` locally (root `CLAUDE.md`: never edit `settings.php` directly) and never in code or knowledge docs. The password has been in the repo, so rotate it if it's used anywhere else. Production is AcuGIS, which can't reach a LAN host, so `external_pg` can't work in production; it's deprecated in favor of the TrailMapper API. Until it's deleted, any credentials belong in the server's `settings.local.php`, outside the repo.

## Database
- Raw inserts of uid 0 need NO_AUTO_VALUE_ON_ZERO in the session SQL mode, or MariaDB auto-numbers it.
- MariaDB's default collation is case-insensitive; data from PostgreSQL can collide on unique keys that differ only by case.
- The Lando-era MariaDB client required SSL by default (historical; DDEV not re-verified, [GAP]); the server has none. `drush sql:*` fails with "SSL is required, but the server does not support it". Drush runs the client with `--defaults-file`, so global client config in `/etc/mysql` is ignored. Pass the flag per command: `lando drush sql:query "..." --extra=--skip-ssl`, `lando drush sql:dump --extra-dump=--skip-ssl --result-file=...` (verified 2026-10-23). No persistent local-only setting was found (drush.yml and `DRUSH_OPTIONS_*` env vars did not take effect); don't commit `--skip-ssl` to shared drush config without checking the server's client (2026-10-23).

## Hosting
- AcuGIS (host us19): repo root on the server is `/home/roundybr/public_html/seanmontague`, Drupal root its `public_html/`, Drupal 11.3.22. CLI `php` is 8.3.36; `/opt/alt/php83` and `/opt/alt/php84` do not exist, so don't reference them (2026-11-19).
- The server CLI `memory_limit` is too low for a cache rebuild: `drush cr` dies with "Drush command terminated abnormally" and no error. Run `php -d memory_limit=512M vendor/drush/drush/drush.php <command>` from the repo root (2026-11-19).
- A module's routes (e.g. `trail_mapper_planner`'s `/kingdom-trails/map` and `/kingdom-trails/map/network.json`) only register on a successful cache rebuild. "Module enabled but 404" on production: check that `drush cr` completed (2026-11-19).
- `trailmapper/config/settings.php` is gitignored and must be placed on the server by hand (SFTP), `chmod 600`; without it the planner returns 503 (2026-11-19).
- Remote MySQL on AcuGIS is unencrypted. Move databases by streaming over SSH: `gunzip -c dump.sql.gz | ssh <user>@<host> "mysql <db>"`, with credentials in `~/.my.cnf` on the server, never in commands or docs (2026-11-19).

## Accessibility references
- axe color contrast: https://dequeuniversity.com/rules/axe/4.11/color-contrast?application=axeAPI
- axe link in text block: https://dequeuniversity.com/rules/axe/4.11/link-in-text-block?application=axeAPI

## Not captured yet
- **[GAP]** Blueprints and JSON-LD, Composer and upgrade history, hosting and DNS, and Drupal theming learnings. The attachments hold none beyond what the two `CLAUDE.md` files already state. Check `SCHEMADOTORG.md`, theme-root `CLAUDE.md`, and the `seanmontague_schemadotorg` README for overlap before adding any.
