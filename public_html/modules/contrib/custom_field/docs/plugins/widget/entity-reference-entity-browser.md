# Entity browser widget
The **Entity browser** (`entity_reference_entity_browser`) widget provides a way
to reference an entity using an [entity browser](https://www.drupal.org/project/entity_browser){target="_blank"}.

## Settings
| Setting                       | Label                                  | Description                                                     | Default |
|-------------------------------|----------------------------------------|-----------------------------------------------------------------|:--------|
| entity_browser                | Entity browser                         | The entity browser to use                                       | NULL    |
| field_widget_display          | Entity display plugin                  | The display plugin options for the allowed entity type          | label   |
| field_widget_display_settings | Entity display plugin configuration    | Additional plugin settings for the selected display plugin      |         |
| field_widget_edit             | Display Edit button                    | Option to show **Edit** button for selected value               | TRUE    |
| field_widget_remove           | Display Remove button                  | Option to show **Remove** button for selected value             | TRUE    |
| field_widget_replace          | Display Replace button                 | Option to show **Replace** button for selected value            | FALSE   |
| open                          | Show widget details as open by default | Expand the wrapping element containing the entity browser input |         |

## Dependencies
- Custom Field - Entity Browser (`custom_field_entity_browser`)
- [Entity Browser](https://www.drupal.org/project/entity_browser){target="_blank"}

## Field types
- [entity_reference](../type/entity-reference.md)
