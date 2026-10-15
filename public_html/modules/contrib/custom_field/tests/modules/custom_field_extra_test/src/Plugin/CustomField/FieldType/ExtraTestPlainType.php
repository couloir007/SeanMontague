<?php

declare(strict_types=1);

namespace Drupal\custom_field_extra_test\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * A subfield type with extra columns that never maps a nested value onto them.
 *
 * Exists so the "no override" half of the extra-property contract can be
 * tested against a type that is guaranteed to keep inheriting the base no-op.
 * Real types get overrides as issues close - link, image, datetime, daterange
 * and time_range all did - which silently turned the assertions written
 * against them into assertions about the opposite behavior.
 *
 * The declared widget and formatter do not list this type in their own
 * field_types, so this plugin is usable for value-level tests only, not for
 * form or display tests.
 */
#[CustomFieldType(
  id: 'extra_test_plain',
  label: new TranslatableMarkup('Extra property test without extraction'),
  description: new TranslatableMarkup('Test type with extra columns and no extraction override.'),
  category: new TranslatableMarkup('Testing'),
  default_widget: 'text',
  default_formatter: 'string',
)]
class ExtraTestPlainType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = ['type' => 'varchar', 'length' => 255];
    $columns[$name . self::SEPARATOR . 'first'] = ['type' => 'varchar', 'length' => 255];
    $columns[$name . self::SEPARATOR . 'second'] = ['type' => 'varchar', 'length' => 255];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]));
    $properties[$name . self::SEPARATOR . 'first'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('First'));
    $properties[$name . self::SEPARATOR . 'second'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Second'));

    return $properties;
  }

}
