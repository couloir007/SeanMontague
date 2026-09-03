# Linkit widget
The **Linkit** (`linkit`) widget provides an autocomplete interface for internal
and external linking. [Linkit](https://www.drupal.org/project/linkit){target="_blank"}
supports linking to all types of entities that define a canonical link template.

## Settings
| Setting               | Label                                              | Description                                                  | Default |
|-----------------------|----------------------------------------------------|--------------------------------------------------------------|:--------|
| placeholder_url       | Placeholder for URL                                | Text shown inside the URL field when empty                   |         |
| placeholder_title     | Placeholder for link text                          | Text shown inside the link text field when empty             |         |
| maxlength             | Max length for link text                           | Maximum amount of characters allowed in the link text field  | 255     |
| maxlength_js          | Show max length character count                    | Display a live character counter below the link text field   | FALSE   |
| linkit_profile        | Linkit profile                                     | The Linkit profile to use for this widget                    | default |
| linkit_auto_link_text | Automatically populate link text from entity label | Sets the link text value with the label from selected entity | FALSE   |

> [!Note]
> The `maxlength_js` setting is visible when the `title` field is enabled and
> the [MaxLength](https://www.drupal.org/project/maxlength){:target="_blank"} module is installed.

## Dependencies
- Custom Field - Linkit integration (`custom_field_linkit`)
- [Linkit](https://www.drupal.org/project/linkit){target="_blank"}

## Field types
- [link](../type/link.md)