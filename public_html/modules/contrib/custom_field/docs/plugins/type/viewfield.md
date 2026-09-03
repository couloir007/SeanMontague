# Viewfield
The **Viewfield** (`viewfield`) custom field type plugin is our version of the popular [Viewfield](https://www.drupal.org/project/viewfield){target="_blank"}
contrib module. It is used to store a reference to a view along with a
display name and options for arguments and number of items to display.

## Field Settings
| Setting          | Label                    | Description                                             | Default |
|------------------|--------------------------|---------------------------------------------------------|---------|
| allowed_views    | Allowed views            | A list of allowed views displays that can be referenced |         |
| force_default    | Always use default value | Force the field to use the default value                | 0       |

### Token browser settings
> [!note]
> These additional settings are provided to handle available tokens for the
> `arguments` field when the [Token](https://www.drupal.org/project/token){target="_blank"} 
> module is enabled. In particular, the default depth of the token tree can be
> modified via the `recursion_limit` setting to access deeper levels of the token
> tree.

| Setting         | Label           | Description                                                          | Default |
|-----------------|-----------------|----------------------------------------------------------------------|---------|
| recursion_limit | Recursion limit | The depth of the token browser tree                                  | 3       |
| global_types    | Global types    | Enable 'global' context tokens like `[current-user:*]` or `[site:*]` | FALSE   |


## Widgets
| Label                                             | Plugin ID        | Default |
|---------------------------------------------------|------------------|---------|
| [Viewfield select](../widget/viewfield-select.md) | viewfield_select | &check; |
| [Hidden](../widget/hidden.md)                     | hidden           |         |

## Formatters
| Label                                  | Plugin ID         | Default |
|----------------------------------------|-------------------|---------|
| [Viewfield](../formatter/viewfield.md) | viewfield_default | &check; |
| [Hidden](../formatter/hidden.md)       | hidden            |         |

## Dependencies
- Views ( `views`)
- Custom Field - Viewfield (`custom_field_viewfield`)
