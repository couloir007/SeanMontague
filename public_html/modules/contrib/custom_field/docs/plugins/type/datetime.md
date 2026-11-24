# Datetime
The **Datetime** (`datetime`) custom field type plugin is used to store date/time and optional
time zone values depending on the *datetime_type* configuration.

## Field Storage
| Setting       | Label     | Description                                       | Default  |
|---------------|-----------|---------------------------------------------------|----------|
| datetime_type | Date type | The type of date (`date`, `datetime` or `allday`) | datetime |

## Field Settings
| Setting          | Label                                 | Description                                                     | Default |
|------------------|---------------------------------------|-----------------------------------------------------------------|---------|
| seconds_enabled  | Add seconds parameter to input widget | Allows seconds in time inputs                                   | TRUE    |
| timezone_enabled | Enable time zone selection            | Allows users to set a time zone for the date value.             | FALSE   |
| timezone_options | Time zone options                     | An array of time zone options when *timezone_enabled* is `TRUE` |         |

## Widgets
| Label                                                | Plugin ID           | Default |
|------------------------------------------------------|---------------------|---------|
| [Date and time](../widget/datetime-default.md)       | datetime_default    | &check; |
| [Date and time (local)](../widget/datetime-local.md) | datetime_local [^1] |
| [Select list](../widget/datetime-datelist.md)        | datetime_datelist   |
| [Hidden](../widget/hidden.md)                        | hidden              |         |

## Formatters
| Label                                         | Plugin ID         | Default |
|-----------------------------------------------|-------------------|---------|
| [Default](../formatter/datetime-default.md)   | datetime_default  | &check; |
| [Advanced](../formatter/datetime-advanced.md) | datetime_advanced |
| [Custom](../formatter/datetime-custom.md)     | datetime_custom   |         |
| [Time ago](../formatter/datetime-time-ago.md) | datetime_time_ago |
| [Hidden](../formatter/hidden.md)              | hidden            |         |

[^1]: The `datetime_local` widget is only available if the *datetime_type*
is `datetime`.