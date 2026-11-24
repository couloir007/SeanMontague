# Custom link attributes for `link` sub-fields

Here are instructions on how to add/remove/modify the available link attributes for [link](../plugins/type/link.md)
subfields.

1. Copy the [custom_field.custom_field_link_attributes.yml](https://git.drupalcode.org/project/custom_field/-/blob/4.0.x/custom_field.custom_field_link_attributes.yml){target="_blank"}
file to the root of your own custom module.
2. Rename the prefix of the file to the machine name of your module. e.g. my_module.custom_field_link_attributes.yml
3. Add/Remove/Modify attributes.
4. Clear cache.

## Examples

Add a new data type attribute as a checkbox:

```yaml
data-download-only:
  title: Download only
  type: checkbox
```

Modify the existing class attribute to be checkboxes of limited options:
```yaml
class:
  title: Class
  description: Select one or more classes from the available options.
  type: checkboxes
  options:
    foo: Foo class
    bar: Bar class
```
