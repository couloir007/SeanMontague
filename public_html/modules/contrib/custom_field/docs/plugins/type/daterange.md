# Date range
The **Date range** (`daterange`) custom field type plugin is used to store a range of start and 
end date/time values, a duration value in seconds and optional time zone value 
depending on the *datetime_type* configuration.

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
| duration_enabled | Enable duration selection             | Calculates end date from duration selection                     | FALSE   |
| duration_options | Duration options                      | An array of duration options when *duration_enabled* is `TRUE`  |         |

## Widgets
| Label                                        | Plugin ID         | Default |
|----------------------------------------------|-------------------|---------|
| [Date range](../widget/daterange-default.md) | daterange_default | &check; |
| [Hidden](../widget/hidden.md)                | hidden            |         |

## Formatters
| Label                                        | Plugin ID         | Default |
|----------------------------------------------|-------------------|---------|
| [Default](../formatter/daterange-default.md) | daterange_default | &check; |
| [Duration](../formatter/duration.md)         | duration          |
| [Hidden](../formatter/hidden.md)             | hidden            |         |