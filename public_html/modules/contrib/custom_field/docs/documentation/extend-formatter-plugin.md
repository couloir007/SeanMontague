# How to extend a formatter plugin
While the module provides an extensive set of [formatters](../plugins/formatter/index.md) out of the box, we
provide the ability to extend them further to meet your specific needs.

1. Review the existing formatters in the module folder: `/modules/contrib/custom_field/src/Plugin/CustomField/FieldFormatter`
2. Create the plugin directory in your module: `/src/Plugin/CustomField/FieldFormatter`
3. Copy one of the formatters from step #1 into this directory.
4. Rename the file and class name accordingly.
5. Open the file and modify the plugin attributes:
    - **id** – Change the id to a unique name (required). Prefixing with your module name is recommended.
    - **label** – Change the label to a human-friendly name that will differentiate it from other widgets in the UI.
    - **description** – Provide a description (optional)
    - **field_types** – At least one field_type is required. In most cases, you
    will want to maintain the original value from the plugin you're extending to
    prevent unexpected issues of incompatibility.
6. Modify the existing methods in the class.
7. Save the file and clear the cache.
8. Edit the field formatter settings on the **Manage display** page.
9. You should now be able to select your new formatter type for a subfield with
a matching field type.

Here's an example of a class that extends the [TelephoneLinkFormatter](../plugins/formatter/telephone-link.md) plugin:

```php
<?php
namespace Drupal\my_module\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Plugin\CustomField\FieldFormatter\TelephoneLinkFormatter;

/**
 * Plugin implementation of the 'my_module_custom_telephone_link' formatter.
 */
#[FieldFormatter(
  id: 'my_module_custom_telephone_link',
  label: new TranslatableMarkup('Telephone link (My Module)'),
  field_types: [
    'telephone',
  ],
)]
class CustomTelephoneLinkFormatter extends TelephoneLinkFormatter {
  // My custom code.
}
```