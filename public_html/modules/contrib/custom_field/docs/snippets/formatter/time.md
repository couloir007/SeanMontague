--8<-- [start:formatter_settings_time_format]

The `time_format_parts` setting controls the formatting of the time by breaking it into individual sortable components.

Each component can be configured to display in a php time **format** or hidden. An optional **suffix** can be appended to the component to serve as a separator.

**Hour**

| Setting | Label  | Description         | Default |
|---------|--------|---------------------|---------|
| format  | Format | PHP date format     | g       |
| suffix  | Suffix | Text appended after | `:`     |

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