# URI
The **URI** (`uri`) custom field type plugin is used to store a URI string.

## Field settings
| Setting             | Label                            | Description                                                  | Default |
|---------------------|----------------------------------|--------------------------------------------------------------|---------|
| link_type           | Allowed link type                | Determines the allowed url input: internal, external or both | both    |
| field_prefix        | Field prefix                     | Enables option to override the url prefix                    | default |
| field_prefix_custom | Custom field prefix              | An input field to set custom url prefix                      |         |

## Widgets
| Label                             | Plugin ID  | Default |
|-----------------------------------|------------|---------|
| [Url](../widget/url.md)           | url        | &check; |
| [Linkit](../widget/linkit-url.md) | linkit_url |         |
| [Hidden](../widget/hidden.md)     | hidden     |         |

## Formatters
| Label                                | Plugin ID  | Default |
|--------------------------------------|------------|---------|
| [Link](../formatter/uri-link.md)     | uri_link   | &check; |
| [Linkit](../formatter/linkit-url.md) | linkit_url |         |
| [Plain text](../formatter/string.md) | string     |         |
| [Hidden](../formatter/hidden.md)     | hidden     |         |
