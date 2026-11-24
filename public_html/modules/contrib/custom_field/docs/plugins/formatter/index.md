# Subfield formatters
Every subfield in a Custom Field has its own configurable **formatter** — controlling
how that subfield's value is displayed when the entity is rendered. Some subfield types
offer more than one formatter to choose from (for example, a link subfield can render
as a plain link, custom link text, or via Linkit), and the available formatter is
configured per subfield in the field's display settings.

## Available formatters

- [Boolean](boolean.md)
- Date and time
    - [Default](datetime-default.md)
    - [Advanced](datetime-advanced.md)
    - [Custom](datetime-custom.md)
    - [Time ago](datetime-time-ago.md)
- [Date range](daterange-default.md)
- [Duration](duration.md)
- [Email](email.md)
- Entity reference
    - [Label](entity-reference-label.md)
    - [Entity ID](entity-reference-entity-id.md)
    - [Rendered entity](entity-reference-entity-view.md)
    - [Hierarchical term](hierarchical-term.md)
- [Generic file](file-default.md)
- [Hidden](hidden.md)
- Image
    - [Image](image.md)
    - [URL to image](image-url.md)
- Link
    - [Link (default)](link.md)
    - [Link (text)](link-text.md)
    - [Linkit](linkit.md)
- Map (serialized array)
    - [Inline](map-inline.md)
    - [List](map-list.md)
    - [Table](map-table.md)
- Numeric
    - [Decimal](number-decimal.md)
    - [Integer](number-integer.md)
    - [Unformatted](number-unformatted.md)
- [Telephone link](telephone-link.md)
- Text
    - [Text (plain)](string.md)
    - [Text (default)](text-default.md)
- Time
    - [Default](time.md)
    - [Advanced](time-advanced.md)
- [Time range](time-range.md)
- URL
    - [Link](uri-link.md)
    - [Linkit](linkit-url.md)
- [URL to file](file-url-plain.md)
- [Viewfield](viewfield.md)

## Extending formatters
If none of the built-in formatters cover your use case, you can create your own by
extending an existing formatter plugin.

See [How to extend a formatter plugin](../../documentation/extend-formatter-plugin.md) for more information.