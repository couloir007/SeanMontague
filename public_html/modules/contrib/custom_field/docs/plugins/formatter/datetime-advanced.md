# Datetime (advanced) formatter
The **Advanced** (`datetime_advanced`) formatter provides a user-friendly way to configure
the date and time formats with selectable formats and optional suffixes as 
sortable parts.

## Settings

--8<-- "formatter/date.md:formatter_settings_timezone"

--8<-- "formatter/date.md:formatter_settings_date_format"

--8<-- "formatter/date.md:formatter_settings_time_format"

### Additional settings

> [!NOTE]
> These settings are only available for `datetime` types.

| Setting                | Label                  | Description                                                      | Default         |
|------------------------|------------------------|------------------------------------------------------------------|-----------------|
| date_first             | First part shown       | Controls the order to show **date** and **time**                 | date            |
| date_time_separator    | Date/time separator    | Text to separate **date** and **time**                           | `#!html &nbsp;` |

### Style settings
--8<-- "formatter/global.md:formatter_settings_wrapper"

## Field types
- [datetime](../type/datetime.md)