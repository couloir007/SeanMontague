# Feeds integration
The [Feeds](https://www.drupal.org/project/feeds){:target="_blank"} module imports
external data (CSV, XML, JSON, etc.) into Drupal entities by mapping source columns to
field targets. Custom Field ships a **Feeds target** plugin for every subfield data
type, so each subfield in a Custom Field can be mapped and configured individually,
right alongside your entity's other fields, with no custom integration code required.

**Feeds** discovers the correct target plugin for a subfield automatically by matching
plugin IDs to the subfield's data type.

## Feeds target plugins

**Text**

- **`string`** (`StringTarget`) — Plain text values. Trims whitespace and discards
  values that end up empty.
- **`string_long`** (`StringLongTarget`) — Long text values, with the same trim/empty
  handling as `string`.
- **`telephone`** (`TelephoneTarget`) — Telephone numbers. Extends `string` with no
  additional validation.
- **`email`** (`EmailTarget`) — Validates the value is a well-formed email address,
  discarding anything invalid. Includes a "Defuse email addresses" option that prefixes
  imported addresses with `FEEDS_TEST_`, so test imports can't accidentally email real
  people.
- **`color`** (`ColorTarget`) — Normalizes hex color values: strips a leading `#`,
  expands 3-digit shorthand (`f00` → `ff0000`), and returns an uppercase `#RRGGBB`
  string, discarding anything that isn't a valid hex color.

**Numeric**

- **`integer`** (`IntegerTarget`) — Casts numeric values to an integer; discards
  non-numeric input.
- **`float`** (`FloatTarget`) — Casts numeric values to a float; discards non-numeric
  input.
- **`decimal`** (`DecimalTarget`) — Casts numeric values to a float; discards
  non-numeric input.
- **`duration`** (`DurationTarget`) — Duration values, stored as seconds. Extends
  `integer` with no changes.
- **`boolean`** (`BooleanTarget`) — Converts common truthy/falsy representations
  (booleans, strings, scalars, single-item arrays) into a boolean value.

**Date & time**

- **`datetime`** (`DatetimeTarget`) — Parses a wide range of date input (timestamps,
  4-digit years, or any string PHP's `strtotime()` understands) into the field's
  storage format. Includes a configurable fallback timezone, used only when the
  source value doesn't specify one.
- **`daterange`** (`DaterangeTarget`) — Start value of a date range. Extends `datetime`
  with no changes.
- **`daterange_end`** (`DaterangeEndTarget`) — End value of a date range. Extends
  `daterange` with no changes.
- **`time`** (`TimeTarget`) — Converts an HTML5 time-format string into a UNIX
  timestamp.
- **`time_range`** (`TimeRangeTarget`) — Start value of a time range. Extends `time`
  with no changes.
- **`time_range_end`** (`TimeRangeEndTarget`) — End value of a time range. Extends
  `time_range` with no changes.

**URI & link**

- **`uri`** (`UriTarget`) — Normalizes incoming values into valid Drupal URI syntax:
  recognizes `<nolink>`/`<none>`/`<button>` route links, maps `<front>` and
  root-relative paths to `internal:` URIs, and validates absolute URLs — discarding
  anything that doesn't resolve to a valid URI.
- **`link`** (`LinkTarget`) — Link values. Extends `uri` with identical normalization.

**Entity & file references**

- **`entity_reference`** (`EntityReferenceTarget`) — Looks up (or optionally
  autocreates) a referenced entity by a configurable field — label, ID, GUID, etc. —
  restricted to allowed bundles.
- **`file`** (`FileTarget`) — Extends `entity_reference`. Downloads a remote file from
  a URL (or looks up an existing file), saves it according to the configured directory
  and existing-file handling (replace/rename/ignore), and returns the resulting file
  entity's ID.
- **`image`** (`ImageTarget`) — Image values. Extends `file` with the same
  download/lookup/save behavior.

**Serialized**

- **`map`** (`MapTarget`) — Decodes a JSON-encoded key/value string into an array,
  discarding invalid or empty data.
- **`map_string`** (`MapStringTarget`) — Plain-text serialized key/value values.
  Extends `map` with no changes.

## Dependencies
- [Feeds](https://www.drupal.org/project/feeds){:target="_blank"} (`feeds`)