# Sort plugins
Custom Field provides a sort plugin for [datetime](../../plugins/type/datetime.md)
and [daterange](../../plugins/type/daterange.md) subfields, since core's sort
handler expects timestamp-based storage.

## `custom_field_datetime`
**Class:** `CustomFieldDate`

Sort handler for [datetime](../../plugins/type/datetime.md) and [daterange](../../plugins/type/daterange.md)
subfields. Extends core's `Date` sort plugin, adapted for string-based date
storage rather than UNIX timestamps, and skips timezone offset calculation for
date-only subfields. Also supports granularity, allowing dates to be treated as
equivalent when sorted by a coarser unit (e.g. grouping by day or month rather
than the exact timestamp).
