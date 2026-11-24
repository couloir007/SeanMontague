<?php

declare(strict_types=1);

namespace Drupal\custom_field_extra_test\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldType;

/**
 * Inherits its extraction from ExtraTestType without re-declaring it.
 *
 * Proves that CustomItem::extraPropertyMap() skips only types that inherit the
 * base no-op, not types that inherit a real override from an ancestor.
 */
#[CustomFieldType(
  id: 'extra_test_child',
  label: new TranslatableMarkup('Extra property test child'),
  description: new TranslatableMarkup('Inherits its extraction from extra_test.'),
  category: new TranslatableMarkup('Testing'),
  default_widget: 'text',
  default_formatter: 'string',
)]
class ExtraTestChildType extends ExtraTestType {

}
