# Date range widget
The **Date range** (`daterange_default`) widget provides a user-friendly
interface for selecting a start and end date (and optionally time).

It uses native HTML5 inputs that adapt to the field's storage configuration:

- When `datetime_type` is set to `date` or `allday`, it renders separate
  `<input type="date">` elements.
- When `datetime_type` is set to `datetime`, it uses the
  `<input type="datetime-local">` element for each part of the range.

When `datetime_type` is set to `datetime`, an optional timezone field can also
be enabled via configuration. This makes it ideal for capturing time periods
such as event schedules, booking durations, project timelines, or
availability windows.

If the field's **Enable duration selection** setting is on, the widget also
shows a **Duration** select populated from the field's configured duration
options (e.g. "1 day", "1 week"). Picking one of these auto-calculates the
end date from the start date, hiding the end date field entirely. Selecting
**Custom** reveals the end date field again for manual entry. This setting
and its options list live on the field's storage/type configuration, not on
this widget's own settings form.

## Settings
| Setting           | Label             | Description                                                                   | Default    |
|-------------------|-------------------|-------------------------------------------------------------------------------|------------|
| year_range        | Year range start  | Sets min/max attributes for start date year                                   | 1900:2050  |
| year_range_end    | Year range end    | Sets min/max attributes for end date year                                     | 1900:2050  |
| start_label       | Start date label  | Label for start date                                                          | Start date |
| end_label         | End date label    | Label for end date                                                            | End date   |
| date_end_required | Require end date  | Whether the end date is required when start date is provided                  | FALSE      |
| all_day_checkbox  | All day checkbox  | Whether to show a checkbox for selecting all day events for `datetime` dates  | FALSE      |
| same_day_checkbox | Same day checkbox | Whether to show a checkbox for selecting same day events for `datetime` dates | FALSE      |

## Field types

- [daterange](../type/daterange.md)