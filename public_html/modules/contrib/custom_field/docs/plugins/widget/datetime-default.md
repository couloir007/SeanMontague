# Date and time widget
The **Date and time** (`datetime_default`) widget provides the `<input type="date">`
and `<input type="time">` elements depending on the storage configuration 
setting for *datetime_type*. An optional time zone field can be specified via 
configuration when the *datetime_type* is set to `datetime`.

## Settings
| Setting    | Label      | Description                                 | Default   |
|------------|------------|---------------------------------------------|-----------|
| year_range | Year range | Sets min/max attributes for start date year | 1900:2050 |

## Field types
- [datetime](../type/datetime.md)