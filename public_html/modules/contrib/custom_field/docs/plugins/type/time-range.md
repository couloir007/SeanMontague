# Time range
The **Time range** (`time_range`) custom field type plugin is used to store a duration 
consisting of start and end times.

## Field settings
| Setting         | Label                                 | Description                      | Default |
|-----------------|---------------------------------------|----------------------------------|---------|
| seconds_enabled | Add seconds parameter to input widget | Enable/disable the seconds input | FALSE   |
| seconds_step    | Step to change seconds                | The `step` attribute granularity | 5       |

## Widgets
| Label                                 | Plugin ID  | Default |
|---------------------------------------|------------|---------|
| [Time range](../widget/time-range.md) | time_range | &check; |
| [Hidden](../widget/hidden.md)         | hidden     |         |


## Formatters
| Label                                 | Plugin ID          | Default |
|---------------------------------------|--------------------|---------|
| [Default](../formatter/time-range.md) | time_range_default | &check; |
| [Hidden](../formatter/hidden.md)      | hidden             |         |