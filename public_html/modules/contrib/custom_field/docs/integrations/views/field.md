# Field plugins
Custom Field provides a single, general-purpose Views field plugin that covers every
subfield type, rather than one plugin per type. It dispatches internally based on the
subfield's data type so each value is resolved and rendered the same way it would be
by the field's own formatter — not as a raw database value.

## `custom_field`
**Class:** `CustomField`

Renders a Custom Field subfield using its configured formatter instead of the raw stored
value.

Handles:

- **Multiple values** — respects "Display all values in the same row" grouping, delta
  limits/offsets, and "first and last only" display options.
- **Formatter-aware rendering** — resolves the proper value shape for reference-based
  types (`entity_reference`, `image`, `file`), `viewfield`, `uri`, `link`, `datetime`,
  `daterange`, `time_range`, and `map`/`map_string` subfields before handing off to the
  subfield's formatter plugin.
- **Replacement tokens** — exposes both the rendered value and the raw value (plus any
  extra columns the subfield defines, e.g. a link's `title` or a daterange's `end_date`)
  as rewrite tokens.
