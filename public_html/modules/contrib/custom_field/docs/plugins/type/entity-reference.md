# Entity reference
The **Entity reference** (`entity_reference`) custom field type plugin is used store an entity id 
associated with a referenced entity.

## Field Storage
| Setting     | Label                     | Description                            |
|-------------|---------------------------|----------------------------------------|
| target_type | Type of item to reference | A content or configuration entity type |

## Widgets
| Label                                                          | Plugin ID                       | Default |
|----------------------------------------------------------------|---------------------------------|---------|
| [Autocomplete](../widget/entity-reference-autocomplete.md)     | entity_reference_autocomplete   | &check; |
| [Radios](../widget/entity-reference-radios.md)                 | entity_reference_radios         |         |
| [Select](../widget/entity-reference-select.md)                 | entity_reference_select         |         |
| [Media library](../widget/media-library.md)                    | media_library_widget            |         |
| [Entity browser](../widget/entity-reference-entity-browser.md) | entity_reference_entity_browser |         |
| [Hierarchical select](../widget/hierarchical-select.md)        | hierarchical_select             |         |
| [Hidden](../widget/hidden.md)                                  | hidden                          |         |

## Formatters
| Label                                                           | Plugin ID                    | Default |
|-----------------------------------------------------------------|------------------------------|---------|
| [Label](../formatter/entity-reference-label.md)                 | entity_reference_label       | &check; |
| [Entity ID](../formatter/entity-reference-entity-id.md)         | entity_reference_entity_id   |         |
| [Rendered entity](../formatter/entity-reference-entity-view.md) | entity_reference_entity_view |         |
| [Hierarchical term](../formatter/hierarchical-term.md)          | hierarchical_term_formatter  |         |
| [Hidden](../formatter/hidden.md)                                | hidden                       |         |