# Duration widget
The **Duration** (`duration`) widget provides two ways to enter a duration,
controlled by the **Duration element** setting.

**Pre-defined options** (the default) renders a select list of durations
keyed by the duration in seconds, with the options derived from the
`duration_options` field setting.

**Input fields** renders separate day/hour/minute number inputs, which are
combined into a total number of seconds on save.

## Settings
| Setting          | Label            | Description                                                          | Default |
|------------------|------------------|----------------------------------------------------------------------|:--------|
| duration_element | Duration element | They type of form element (Pre-defined options list or input fields) | options |

## Field types
- [duration](../type/duration.md)
