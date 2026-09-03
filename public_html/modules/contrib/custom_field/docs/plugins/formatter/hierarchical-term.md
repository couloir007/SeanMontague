# Hierarchical term formatter
The **Hierarchical term** (`hierarchical_term_formatter`) formatter displays terms in a hierarchical
format.

> [!note]
> This formatter is only available for entity reference fields with a `target_type` of `taxonomy_term`.

## Settings

| Setting             | Label            | Description                                                          | Default |
|---------------------|------------------|----------------------------------------------------------------------|---------|
| hierarchy_display   | Terms to display | Option to filter the output of terms to display based on their depth | all     |
| hierarchy_link      | Link each term   | Option to output each term as a link to their term page              | FALSE   |
| hierarchy_reverse   | Reverse order    | Option to reverse the term order so children display first           | FALSE   |
| hierarchy_wrap      | Wrap each term   | Optional HTML element to wrap each term in                           | none    |
| hierarchy_separator | Separator        | Text or markup that separates each term in the hierarchy             | »       |

### Style settings
--8<-- "formatter/global.md:formatter_settings_wrapper"

## Field types
- [entity_reference](../type/entity-reference.md)
