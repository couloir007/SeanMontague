# Decimal formatter
The **Decimal** (`number_decimal`) formatter displays the field value as a formatted decimal number.

## Settings

| Setting            | Label                     | Description                                                                                               | Default |
|--------------------|---------------------------|-----------------------------------------------------------------------------------------------------------|---------|
| thousand_separator | Thousand marker           | A punctuation mark (like a comma, dot, or space) used in large numbers to group digits into sets of three | ,       |
| key_label          | Display                   | The `key` or `label` value option to output when the field settings support allowed values                | label   |
| prefix_suffix      | Display prefix and suffix | An option to determine if prefix or suffix from field settings should display in the output               | FALSE   |
| decimal_separator  | Decimal marker            | A symbol that divides the integer and fractional parts of a [decimal](../type/decimal.md) subfield value  | .       |
| scale              | Scale                     | The number of decimal places to display for a [decimal](../type/decimal.md) subfield value                | 2       |

> [!note]
> The `decimal_separator` and `scale` settings are only applicable for [decimal](../type/decimal.md) subfield values.

### Style settings
--8<-- "formatter/global.md:formatter_settings_wrapper"

## Field types
- [decimal](../type/decimal.md)
- [float](../type/float.md)
