# Number (float)
The **Float** (`float`) custom field type plugin is used to store floating point numbers.

## Field Storage
| Setting  | Label    | Description                                                | Default |
|----------|----------|------------------------------------------------------------|---------|
| unsigned | Unsigned | Store only non-negative values (zero and positive numbers) | FALSE   |
| size     | Size     | Determines storage size and value range                    | normal  |

## Field settings
| Setting        | Label          | Description                                          |
|----------------|----------------|------------------------------------------------------|
| min            | Minimum        | The minimum value allowed                            |
| max            | Maximum        | The maximum value allowed                            |
| prefix         | Prefix         | A string that should be prefixed to the value        |
| suffix         | Suffix         | A string that should be suffixed to the value        |
| allowed_values | Allowed values | The allowed values for `select` and `radios` widgets |

## Widgets
| Label                                           | Plugin ID       | Default |
|-------------------------------------------------|-----------------|---------|
| [Float](../widget/float.md)                     | decimal         | &check; |
| [Select](../widget/select.md)                   | select          |         |
| [Radios](../widget/radios.md)                   | radios          |         |
| [Select or Other](../widget/select-or-other.md) | select_or_other |         |
| [Hidden](../widget/hidden.md)                   | hidden          |         |

## Formatters
| Label                                             | Plugin ID          | Default |
|---------------------------------------------------|--------------------|---------|
| [Default](../formatter/number-decimal.md)         | number_decimal     | &check; |
| [Unformatted](../formatter/number-unformatted.md) | number_unformatted |         |
| [Hidden](../formatter/hidden.md)                  | hidden             |         |
