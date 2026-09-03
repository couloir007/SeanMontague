# Table formatter
The **Table** (`custom_table`) formatter renders the custom field items as HTML table.
This formatter is most useful for multi-value fields.

## Settings
| Setting     | Label                        | Description                                                     | Default |
|-------------|------------------------------|-----------------------------------------------------------------|---------|
| sort_by     | Sort by                      | Option to sort by a subfield value                              | _delta  |
| sort_order  | Sort order                   | The sort direction (either `asc` or `desc`)                     | asc     |
| hide_empty  | Hide columns with empty rows | Option to skip rendering a column when all row values are empty | FALSE   |
| hide_header | Hide table header            | Option to hide the table header                                 | FALSE   |
