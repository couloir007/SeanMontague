# Select or Other widget
The **Select or Other** (`select_or_other`) widget provides list of options
with an **other** option. When **other** is selected a textfield appears
for the user to provide a custom value. The list element type is configurable as 
a **list** (`<select>`) or **buttons** (`<input type="radio">`) form element.

## Settings
| Setting             | Label                                                                                 | Description                                                                 | Default    |
|---------------------|---------------------------------------------------------------------------------------|-----------------------------------------------------------------------------|:-----------|
| empty_option        | Empty option                                                                          | The option label to show when the field is not required                     | - Select - |
| select_element_type | Element type                                                                          | The element type `list` or `buttons`                                        | list       |
| other_placeholder   | Other placeholder                                                                     | Text that will be shown inside the **other** field until a value is entered |            |
| other_option        | Label of the option that the user will choose when they want to supply an other value |                                                                             |            |
| other_field_label   | Label of the Other field                                                              | Label for the field in which the user will supply an other value            | Other      |

## Field types
- [string](../type/string.md)
- [float](../type/float.md)
- [integer](../type/integer.md)
