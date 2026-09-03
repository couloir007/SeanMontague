# Text (long)
The **Text (long)** (`string_long`) custom field type plugin is used to store a long text value.

## Field Settings
| Setting           | Label                 | Description                                                     | Default |
|-------------------|-----------------------|-----------------------------------------------------------------|---------|
| formatted         | Enable wysiwyg        | Enables text editor                                             | FALSE   |
| default_format    | Default format        | The default text format to apply when *formatted* is enabled    |         |
| format.guidelines | Show format guidlines | Hide/show format guidelines section when *formatted* is enabled | TRUE    |
| format.help       | Show format help      | Hide/show format help section when *formatted* is enabled       | TRUE    |

## Widgets
| Label                                              | Plugin ID | Default |
|----------------------------------------------------|-----------|---------|
| [Text area (multiple rows)](../widget/textarea.md) | textarea  | &check; |
| [Hidden](../widget/hidden.md)                      | hidden    |         |


## Formatters
| Label                                   | Plugin ID    | Default |
|-----------------------------------------|--------------|---------|
| [Default](../formatter/text-default.md) | text_default | &check; |
| [Plain text](../formatter/string.md)    | string       |         |
| [Hidden](../formatter/hidden.md)        | hidden       |         |