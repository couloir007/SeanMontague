# How to extend a widget plugin
While the module provides an extensive set of [widgets](../plugins/widget/index.md) out of the box, we provide
the ability to extend them further to meet your specific needs.

1. Review the existing widgets in the module folder: `/modules/contrib/custom_field/src/Plugin/CustomField/FieldWidget`
2. Create the plugin directory in your module: `/src/Plugin/CustomField/FieldWidget`
3. Copy one of the widgets from step #1 into this directory.
4. Rename the file and class name accordingly.
5. Open the file and modify the attributes:
    - **id** – Change the id to a unique name (required). Prefixing with your module name is recommended.
    - **label** – Change the label to a human-friendly name that will
    differentiate it from other widgets in the UI.
    - **description** – Provide a description (optional).
    - **category** – Change the category (optional). It will default to
    *General* if no category is provided.
    - **field_types** – At least one field type is required. In most cases, you
    will want to maintain the original value from the plugin you're extending to
    prevent unexpected issues of incompatibility.
6. Modify the existing methods in the class.
7. Save the file and clear the cache.
8. Edit the field widget settings on the **Manage form display** page.
9. You should now be able to select your new widget type for a subfield with a
matching data type.

Here's an example of a class that extends the [SelectWidget](../plugins/widget/select.md) plugin:

```php
<?php

namespace Drupal\my_module\Plugin\CustomField\FieldWidget;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\FieldWidget\SelectWidget;

/**
 * Plugin implementation of the 'my_module_custom_select' widget.
 */
#[CustomFieldWidget(
  id: 'my_module_custom_select',
  label: new TranslatableMarkup('Select list (My module'),
  category: new TranslatableMarkup('Lists'),
  field_types: [
    'string',
    'integer',
    'float',
  ],
)]
class CustomSelectWidget extends SelectWidget {
  // My custom code.
}
```