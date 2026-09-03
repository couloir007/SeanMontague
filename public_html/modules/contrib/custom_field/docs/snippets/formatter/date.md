--8<-- [start:formatter_settings_timezone]
### Time zone

> [!note]
> Some of these settings are only available for `datetime` types.

The `timezone` setting controls the timezone used to display the date and
options to display the timezone for `datetime` types.

| Setting           | Label                                | Description                                                           | Default      |
|-------------------|--------------------------------------|-----------------------------------------------------------------------|--------------|
| timezone_override | Time zone override                   | Optional time zone to always use                                      |              |
| timezone_stored   | Use stored time zone                 | Option to use the stored time zone value when no override is selected | FALSE        |
| display_timezone  | Display time zone                    | Option to display time zone along with the date/time                  | FALSE        |
| timezone_format   | Time zone format                     | The format of the time zone to use when displayed                     | abbreviation |
| user_timezone     | Append date/time in user's time zone | Option to also display user's time zone                               | FALSE        |
--8<-- [end:formatter_settings_timezone]

--8<-- [start:formatter_settings_date_format]
### Date format
The `date_format_parts` setting controls the formatting of the **date**
portion by breaking it into individual sortable components.

Each component can be configured to display in a php date **format** or
hidden. An optional **suffix** can be appended to the component to serve as
a separator.

**Month**

| Setting | Label  | Description | Default |
|---------|--------|-------------|---------|
| format  | Format | Date format | F       |
| suffix  | Suffix | Date suffix | `space` |

**Day**

| Setting | Label  | Description | Default |
|---------|--------|-------------|---------|
| format  | Format | Date format | jS      |
| suffix  | Suffix | Date suffix | ,       |

**Year**

| Setting | Label  | Description | Default |
|---------|--------|-------------|---------|
| format  | Format | Date format | Y       |
| suffix  | Suffix | Date suffix |         |
--8<-- [end:formatter_settings_date_format]

--8<-- [start:formatter_settings_time_format]
### Time format

> [!note]
> These settings are only available for `datetime` types.

The `time_format_parts` setting controls the formatting of the **time**
portion by breaking it into individual sortable components.

Each component can be configured to display in a php date **format** or
hidden. An optional **suffix** can be appended to the component to serve as
a separator.

**Hour**

| Setting | Label  | Description         | Default |
|---------|--------|---------------------|---------|
| format  | Format | PHP date format     | g       |
| suffix  | Suffix | Text appended after | :       |

**Minute**

| Setting | Label  | Description         | Default |
|---------|--------|---------------------|---------|
| format  | Format | PHP date format     | i       |
| suffix  | Suffix | Text appended after |         |

**Second**

| Setting | Label  | Description         | Default |
|---------|--------|---------------------|---------|
| format  | Format | PHP date format     |         |
| suffix  | Suffix | Text appended after |         |

**AM/PM**

| Setting | Label  | Description         | Default |
|---------|--------|---------------------|---------|
| format  | Format | PHP date format     | a       |
| suffix  | Suffix | Text appended after |         |

--8<-- [end:formatter_settings_time_format]