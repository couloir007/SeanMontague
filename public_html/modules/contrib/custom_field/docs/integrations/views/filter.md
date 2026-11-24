# Filter plugins
Custom Field provides filter plugins for [datetime](../../plugins/type/datetime.md),
[daterange](../../plugins/type/daterange.md) and [entity_reference](../../plugins/type/entity-reference.md)
subfields, since none of core's generic filter handlers account for values living inside a Custom Field's own subfield columns.

## `custom_field_datetime`
**Class:** `CustomFieldDate`

Filters [datetime](../../plugins/type/datetime.md) and [daterange](../../plugins/type/daterange.md) subfields.
Extends core's numeric `Date` filter (rather than the string filter) to get sensible date comparison operators, while formatting
comparisons using string-based date SQL to match how Custom Field stores its values.

Adds a value-type toggle so the exposed filter can accept either an absolute date or a
relative offset (e.g. `+1 day`, `-2 hours -30 minutes`), and adapts its exposed widget
based on whether the underlying subfield stores a date-only or full datetime value.

## `custom_field_entity_reference`
**Class:** `CustomFieldEntityReference`

Filters [entity_reference](../../plugins/type/entity-reference.md) subfields.
Extends core's `EntityReference` filter, but scopes the autocomplete/select
value options to only the entities actually referenced in that specific 
subfield's column — via a subquery against the Custom Field table — rather than
every entity of the target type. Also supports restricting the option list by
target bundle.
