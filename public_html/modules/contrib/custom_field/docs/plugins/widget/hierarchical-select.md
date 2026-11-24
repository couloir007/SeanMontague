# Hierarchical select widget
The **Hierarchical select** (`hierarchical_select`) widget provides a series
of `select` elements that allow the user to select a term from a hierarchical
taxonomy term tree.

> [!note]
> This widget is only available for entity reference fields with a `target_type` of `taxonomy_term`.

## Settings
| Setting             | Label               | Description                                                        | Default |
|---------------------|---------------------|--------------------------------------------------------------------|:--------|
| force_deepest_level | Force deepest level | Require the deepest level of the taxonomy term tree to be selected | FALSE   |
| level_labels        | Show level labels   | Show labels above widgets for each level                           | TRUE    |

## Dependencies
- Taxonomy (`taxonomy`)

## Field types
- [entity_reference](../type/entity-reference.md)
