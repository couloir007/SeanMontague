# Learnings and gotchas: SeanMontague.com build

Project-level gotchas the repo docs don't cover. Code, theme, and Blueprints gotchas belong in the repo docs (root `CLAUDE.md`, the Surface `CLAUDE.md` files, `SCHEMADOTORG.md`, `STORYBOOK.md`), where Claude Code also reads them. Add an entry here only when it fits nowhere in the repo: environment, hosting, dependency history, doc drift, open security items.

One bullet per learning: what happened, the rule, and the date or source it was checked against.

Lives in `docs/`; the Claude project copy is refreshed from it. Entries marked **[GAP]** need a fact the attachments don't supply.

## Tooling
- Junie retired 2026-10-22; Claude Code in PhpStorm is the only coding agent. `.junie/guidelines.md` was already stale: it named the project `roundybrookfarm`, ran `composer` and `npm` without `lando`, and scaffolded tests as a plain PHP script in a root `tests/Unit/` directory. Rule: delete `.junie/` from the repo; if any copy survives, root `CLAUDE.md` wins.
- The repo `README.md` is one heading, `# roundybrookfarm`, and carries no project information.

## Security and config
- `external_pg` credentials (`external_pg` is deprecated; see decisions.md): verified hardcoded in `ExternalPgService.php` (2026-10-22 module snapshot), with a private LAN host, and the constructor echoes a connection message. Rule: credentials belong in `settings.lando.php` locally (root `CLAUDE.md`: never edit `settings.php` directly) and never in code or knowledge docs. The password has been in the repo, so rotate it if it's used anywhere else. **[GAP]** Production is AcuGIS. A LAN host is unreachable from it, and credentials go in the server's `settings.local.php`, outside the repo.

## Hosting
- AcuGIS uses CloudLinux PHP Selector. Web PHP is set per directory, and the CLI default differs, so run Drush and Composer on the server through `/opt/alt/php84/usr/bin/php`.

## Accessibility references
- axe color contrast: https://dequeuniversity.com/rules/axe/4.11/color-contrast?application=axeAPI
- axe link in text block: https://dequeuniversity.com/rules/axe/4.11/link-in-text-block?application=axeAPI

## Not captured yet
- **[GAP]** Blueprints and JSON-LD, Composer and upgrade history, hosting and DNS, and Drupal theming learnings. The attachments hold none beyond what the two `CLAUDE.md` files already state. Check `SCHEMADOTORG.md`, theme-root `CLAUDE.md`, and the `seanmontague_schemadotorg` README for overlap before adding any.
