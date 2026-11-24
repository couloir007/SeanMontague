# Link
The **Link** (`link`) custom field type plugin is used to Store a URL string, optional 
varchar link text, and optional blob of attributes to assemble a link.

## Field settings
| Setting             | Label                            | Description                                                  | Default            |
|---------------------|----------------------------------|--------------------------------------------------------------|--------------------|
| link_type           | Allowed link type                | Determines the allowed url input: internal, external or both | both               |
| field_prefix        | Field prefix                     | Enables option to override the url prefix                    | default            |
| field_prefix_custom | Custom field prefix              | An input field to set custom url prefix                      |                    |
| title               | Allow link text                  | Enables an input field to set the link text                  | 1                  |
| enabled_attributes  | Enable attributes                | Provides checkboxes to enable additional link attributes     | target, rel, class |
| widget_default_open | Attributes default open behavior | Set the attributes details element default open behavior     | expandIfValuesSet  |

> [!tip]Custom link attributes
> Link attributes are handled by the `custom_field.custom_field_link_attributes.yml` file and can easily be
> overridden in your own custom module. Follow these [instructions](../../documentation/link-attributes.md)
> to add, remove or modify the available attributes.

## Widgets
| Label                             | Plugin ID    | Default |
|-----------------------------------|--------------|---------|
| [Link](../widget/link-default.md) | link_default | &check; |
| [Linkit](../widget/linkit.md)     | linkit       |         |
| [Hidden](../widget/hidden.md)     | hidden       |         |

## Formatters
| Label                                  | Plugin ID | Default |
|----------------------------------------|-----------|---------|
| [Link](../formatter/link.md)           | link      | &check; |
| [Link text](../formatter/link-text.md) | link_text |         |
| [Linkit](../formatter/linkit.md)       | linkit    |         |
| [Hidden](../formatter/hidden.md)       | hidden    |         |
