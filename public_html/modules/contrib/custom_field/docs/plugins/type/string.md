# Text (plain)
The **Text (plain)** (`string`) custom field type plugin is used to store a plain string value.

## Field Storage
| Setting | Label  | Description                                   | Default |
|---------|--------|-----------------------------------------------|---------|
| length  | Length | The maximum length of the field in characters | 255     |

## Field Settings
| Setting        | Label          | Description                                          |
|----------------|----------------|------------------------------------------------------|
| prefix         | Prefix         | A string that should be prefixed to the value        |
| suffix         | Suffix         | A string that should be suffixed to the value        |
| allowed_values | Allowed values | The allowed values for `select` and `radios` widgets |

## Widgets
| Label                                           | Plugin ID       | Default |
|-------------------------------------------------|-----------------|---------|
| [Text](../widget/text.md)                       | text            | &check; |
| [Select](../widget/select.md)                   | select          |         |
| [Radios](../widget/radios.md)                   | radios          |         |
| [Select or Other](../widget/select-or-other.md) | select_or_other |         |
| [Hidden](../widget/hidden.md)                   | hidden          |         |
