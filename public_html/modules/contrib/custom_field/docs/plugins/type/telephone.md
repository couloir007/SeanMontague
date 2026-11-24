# Telephone
The **Telephone** (`telephone`) custom field type is used to store telephone numbers.

## Field Storage
| Setting | Label  | Description                                   | Default |
|---------|--------|-----------------------------------------------|---------|
| length  | Length | The maximum length of the field in characters | 256     |

## Field Settings
| Setting        | Label            | Description                                          |
|----------------|------------------|------------------------------------------------------|
| prefix         | Prefix           | A string that should be prefixed to the value        |
| suffix         | Suffix           | A string that should be suffixed to the value        |
| allowed_values | Allowed values   | The allowed values for `select` and `radios` widgets |
| pattern        | Telephone format | A validation pattern to enforce  on the input        |

## Widgets
| Label                               | Plugin ID | Default |
|-------------------------------------|-----------|---------|
| [Telephone](../widget/telephone.md) | telephone | &check; |
| [Hidden](../widget/hidden.md)       | hidden    |         |


## Formatters
| Label                                            | Plugin ID      | Default |
|--------------------------------------------------|----------------|---------|
| [Telephone link](../formatter/telephone-link.md) | telephone_link | &check; |
| [Plain text](../formatter/string.md)             | string         |         |
| [Hidden](../formatter/hidden.md)                 | hidden         |         |