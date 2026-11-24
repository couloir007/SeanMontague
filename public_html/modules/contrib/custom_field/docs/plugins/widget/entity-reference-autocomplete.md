# Autocomplete widget
The **Autocomplete** (`entity_reference_autocomplete`) widget provides a textfield with
autocomplete suggestions of entity labels.

## Settings
| Setting        | Label                 | Description                                                       | Default  |
|----------------|-----------------------|-------------------------------------------------------------------|----------|
| match_operator | Autocomplete matching | The method used to collect autocomplete suggestions               | CONTAINS |
| match_limit    | Number of results     | The number of suggestions to display                              | 10       |
| size           | Size of textfield     | The visible width of the input element defined by character count | 60       |
| placeholder    | Placeholder           | The text to display when the input field is empty                 |          |

## Field types
- [entity_reference](../type/entity-reference.md)
