<?php

declare(strict_types=1);

namespace Drupal\custom_field_extra_test\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * A subfield type that deliberately returns an undeclared extra property.
 *
 * Used to prove that CustomItem::setValue() only accepts extra properties the
 * type actually declares.
 *
 * The declared widget and formatter do not list this type in their own
 * field_types, so this plugin is usable for value-level tests only, not for
 * form or display tests.
 */
#[CustomFieldType(
  id: 'extra_test',
  label: new TranslatableMarkup('Extra property test'),
  description: new TranslatableMarkup('Test type with one declared and one undeclared extra property.'),
  category: new TranslatableMarkup('Testing'),
  default_widget: 'text',
  default_formatter: 'string',
)]
class ExtraTestType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = ['type' => 'varchar', 'length' => 255];
    $columns[$name . self::SEPARATOR . 'good'] = ['type' => 'varchar', 'length' => 255];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]));
    $properties[$name . self::SEPARATOR . 'good'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Good'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * Returns one declared property and one that is not declared anywhere.
   */
  public static function extractExtraPropertyValues(array $settings, mixed $value): array {
    if (!is_array($value)) {
      return [];
    }
    ['name' => $name] = $settings;

    return [
      $name . self::SEPARATOR . 'good' => $value['good'] ?? NULL,
      $name . self::SEPARATOR . 'bogus' => $value['bogus'] ?? NULL,
    ];
  }

}
