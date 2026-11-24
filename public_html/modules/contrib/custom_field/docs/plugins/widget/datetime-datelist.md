# Date and time (select) widget
The **Select list** (`datetime_datelist`) date and time widget provides select boxes for individual date and
time parts.

## Settings
| Setting    | Label           | Description                                                     | Default   |
|------------|-----------------|-----------------------------------------------------------------|:----------|
| year_range | Year range      | Sets min/max year options                                       | 1900:2050 |
| date_order | Date part order | The order of the date parts                                     | YMD       |
| time_type  | Time type       | The type of time (12 or 24 hours) for `datetime` types          | 24        |
| increment  | Time increments | The increment for the time part in minutes for `datetime` types | 15        |

> [!Note]
> `time_type` and `increment` are only editable when the subfield's
> `datetime_type` is `datetime`. For date-only fields they are stored as
> hidden values (`time_type` = `none`).

## Field types
- [datetime](../type/datetime.md)