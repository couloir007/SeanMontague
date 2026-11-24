# Subfield widgets
Every subfield in a Custom Field has its own configurable **widget** — the form
element used to collect that subfield's value when editing content. Some subfield
types offer more than one widget to choose from (for example, an entity reference
subfield can use autocomplete, radios, a select list, and more), and the available
widget is configured per subfield in the field's settings.

## Available widgets

- [Checkbox](checkbox.md)
- Color
    - [Color (default)](color.md)
    - [Color boxes](color-boxes.md)
- Date and time
    - [Date and time (default)](datetime-default.md)
    - [Date and time (local)](datetime-local.md)
    - [Select list](datetime-datelist.md)
- [Date range](daterange-default.md)
- [Duration](duration.md)
- [Email](email.md)
- Entity reference
    - [Autocomplete](entity-reference-autocomplete.md)
    - [Radios](entity-reference-radios.md)
    - [Select](entity-reference-select.md)
    - [Media library](media-library.md)
    - [Entity browser](entity-reference-entity-browser.md)
    - [Hierarchical select](hierarchical-select.md)
- [File](file-generic.md)
- [Hidden](hidden.md)
- [Image](image-image.md)
- Link
    - [Link (default)](link-default.md)
    - [Linkit](linkit.md)
- Map (serialized array)
    - [Key/Value](map-key-value.md)
    - [Text](map-text.md)
- Number
    - [Decimal](decimal.md)
    - [Float](float.md)
    - [Integer](integer.md)
- [Radios](radios.md)
- [Select](select.md)
- [Telephone](telephone.md)
- [Text](text.md)
- [Textarea](textarea.md)
- [Time](time-widget.md)
- [Time range](time-range.md)
- URL
    - [Url (default)](url.md)
    - [Linkit](linkit-url.md)
- [UUID](uuid.md)
- [Viewfield select](viewfield-select.md)

## Extending widgets
If none of the built-in widgets cover your use case, you can create your own by
extending an existing widget plugin.

See [How to extend a widget plugin](../../documentation/extend-widget-plugin.md) for more information.