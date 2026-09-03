# Decimal
The **Decimal** (`decimal`) custom field type plugin is used to Store a number in the database
in a fixed decimal format. Ideal for exact counts and measures. (prices, 
temperatures, distances, volumes, etc.)

## Field Storage
| Setting   | Label     | Description                                                | Default |
|-----------|-----------|------------------------------------------------------------|---------|
| unsigned  | Unsigned  | Store only non-negative values (zero and positive numbers) | FALSE   |
| precision | Precision | The total number of significant digits stored              | 10      |
| scale     | Scale     | The number of digits to the right of the decimal point     | 2       |

## Field settings
| Setting        | Label          | Description                                          |
|----------------|----------------|------------------------------------------------------|
| min            | Minimum        | The minimum value allowed                            |
| max            | Maximum        | The maximum value allowed                            |
| prefix         | Prefix         | A string that should be prefixed to the value        |
| suffix         | Suffix         | A string that should be suffixed to the value        |

## Widgets
| Label                           | Plugin ID | Default |
|---------------------------------|-----------|---------|
| [Decimal](../widget/decimal.md) | decimal   | &check; |
| [Hidden](../widget/hidden.md)   | hidden    |         |

## Formatters
| Label                                             | Plugin ID          | Default |
|---------------------------------------------------|--------------------|---------|
| [Default](../formatter/number-decimal.md)         | number_decimal     | &check; |
| [Unformatted](../formatter/number-unformatted.md) | number_unformatted |         |
| [Hidden](../formatter/hidden.md)                  | hidden             |         |
