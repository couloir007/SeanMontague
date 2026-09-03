# Argument plugins
Custom Field provides contextual filter (argument) plugins for [datetime](../../plugins/type/datetime.md)
and [daterange](../../plugins/type/daterange.md) subfields. Since Custom Field stores date/time values as strings rather than UNIX
timestamps, these handlers extend core's numeric `Date` argument plugin so they read and
format the field using string-based date SQL rather than the timestamp-based logic core
uses for its own datetime fields.

## `custom_field_datetime`
**Class:** `CustomFieldDate`

The base argument handler for [datetime](../../plugins/type/datetime.md) and [daterange](../../plugins/type/daterange.md)
subfields. It detects whether the subfield is stored as date-only or full
datetime and adjusts accordingly — date-only values skip timezone offset 
calculation entirely, since there's no time component to offset. All the 
date-part handlers below extend this class and simply override the PHP date
format used to build the argument.

## `custom_field_datetime_day`
**Class:** `CustomFieldDayDate`

Contextual filter by day of month (`d`).

## `custom_field_datetime_week`
**Class:** `CustomFieldWeekDate`

Contextual filter by ISO week number (`W`).

## `custom_field_datetime_month`
**Class:** `CustomFieldMonthDate`

Contextual filter by month (`m`).

## `custom_field_datetime_year`
**Class:** `CustomFieldYearDate`

Contextual filter by year (`Y`).

## `custom_field_datetime_year_month`
**Class:** `CustomFieldYearMonthDate`

Contextual filter by year and month combined (`Ym`, e.g. `202607`).

## `custom_field_datetime_full_date`
**Class:** `CustomFieldFullDate`

Contextual filter by full date (`Ymd`, e.g. `20260721`).
