# Views
Custom Field provides its own set of **Views** handler plugins so that subfields
behave like native, first-class Views fields — with proper date granularity,
entity reference filtering, and formatter-aware rendering.

## Views plugins

- [Argument](argument.md) — contextual filters for `datetime` and `daterange` subfields.
- [Field](field.md) — formatter-aware rendering of any subfield type.
- [Filter](filter.md) — filters for `datetime`, `daterange` and `entity_reference` subfields.
- [Sort](sort.md) — sorting for `datetime` and `daterange` subfields.

## Viewfield submodule
The optional **Custom Field Viewfield** (`custom_field_viewfield`) submodule
provides the ability to reference and display a view similar to the
functionality provided by the popular [Viewfield](https://www.drupal.org/project/viewfield){:target="_blank"}
module.

It includes the following subfield plugins:

- [Field type](../../plugins/type/viewfield.md)
- [Widget](../../plugins/widget/viewfield-select.md)
- [Formatter](../../plugins/formatter/viewfield.md)
