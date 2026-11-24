# Time ago formatter
The **Time ago** (`datetime_time_ago`) formatter displays the date and time in a human-readable
format in past and future tense.

## Settings

| Setting       | Label         | Description                                                          | Default         |
|---------------|---------------|----------------------------------------------------------------------|-----------------|
| future_format | Future format | Text for future format                                               | @interval hence |
| past_format   | Past format   | Text for past format                                                 | @interval ago   |
| granularity   | Granularity   | How many time interval units should be shown in the formatted output | 2               |

### Style settings
--8<-- "formatter/global.md:formatter_settings_wrapper"

## Field types
- [datetime](../type/datetime.md)