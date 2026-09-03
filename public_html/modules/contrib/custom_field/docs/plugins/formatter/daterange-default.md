# Date range formatter
The **Date range** (`daterange_default`) formatter displays a date range in various formatting
options with additional time zone settings depending on the date type.

## Settings

--8<-- "formatter/date.md:formatter_settings_timezone"

--8<-- "formatter/date.md:formatter_settings_date_format"

--8<-- "formatter/date.md:formatter_settings_time_format"

### Additional settings

> [!NOTE]
> Some of these settings are only available for `datetime` types.

| Setting                | Label                  | Description                                                      | Default |
|------------------------|------------------------|------------------------------------------------------------------|---------|
| date_first             | First part shown       | Controls the order to show **date** and **time**                 | date    |
| date_time_separator    | Date/time separator    | Text to separate **date** and **time**                           | `space` |
| from_to                | Display                | Determines which date values display                             | both    |
| separator              | Date separator         | Separator between date values when both are displayed            | -       |
| end_date_fallback_text | End date fallback text | Optional text to display when the end date is empty              | TBD     |
| all_day_label          | All day label          | The string to output when date range has been set to run all day | All day |
| all_day_separator      | All day separator      | The string to separate the <em>All day label</em> from the dates | \|      |

### Style settings
--8<-- "formatter/global.md:formatter_settings_wrapper"

## Field types

- [daterange](../type/daterange.md)