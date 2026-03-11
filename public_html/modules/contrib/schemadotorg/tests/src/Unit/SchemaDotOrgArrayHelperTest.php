<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Unit;

use Drupal\schemadotorg\Utility\SchemaDotOrgArrayHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for SchemaDotOrgArrayHelper.
 *
 * @group schemadotorg
 */
class SchemaDotOrgArrayHelperTest extends UnitTestCase {

  /**
   * Tests the insertBefore method.
   *
   * @dataProvider providerInsertBefore
   */
  public function testInsertBefore(array $array, string $target_key, string $new_key, mixed $new_value, array $expected): void {
    SchemaDotOrgArrayHelper::insertBefore($array, $target_key, $new_key, $new_value);
    $this->assertSame($expected, $array);
  }

  /**
   * Data provider for testInsertBefore.
   */
  public static function providerInsertBefore(): array {
    return [
      'insert before key2' => [
        ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
        'key2',
        'newKey',
        'newValue',
        ['key1' => 'value1', 'newKey' => 'newValue', 'key2' => 'value2', 'key3' => 'value3'],
      ],
      'insert before key1' => [
        ['key1' => 'value1', 'key2' => 'value2'],
        'key1',
        'newKey',
        'newValue',
        ['newKey' => 'newValue', 'key1' => 'value1', 'key2' => 'value2'],
      ],
    ];
  }

  /**
   * Tests the insertAfter method.
   *
   * @dataProvider providerInsertAfter
   */
  public function testInsertAfter(array $array, string $target_key, string $new_key, mixed $new_value, array $expected): void {
    SchemaDotOrgArrayHelper::insertAfter($array, $target_key, $new_key, $new_value);
    $this->assertSame($expected, $array);
  }

  /**
   * Data provider for testInsertAfter.
   */
  public static function providerInsertAfter(): array {
    return [
      'insert after key2' => [
        ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
        'key2',
        'newKey',
        'newValue',
        ['key1' => 'value1', 'key2' => 'value2', 'newKey' => 'newValue', 'key3' => 'value3'],
      ],
      'insert after key3' => [
        ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
        'key3',
        'newKey',
        'newValue',
        ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'newKey' => 'newValue'],
      ],
    ];
  }

  /**
   * Tests the removeValue method.
   *
   * @dataProvider providerRemoveValue
   */
  public function testRemoveValue(array $array, mixed $value, array $expected): void {
    SchemaDotOrgArrayHelper::removeValue($array, $value);
    $this->assertSame($expected, $array);
  }

  /**
   * Data provider for testRemoveValue.
   */
  public static function providerRemoveValue(): array {
    return [
      'remove existing value' => [
        ['value1', 'value2', 'value3'],
        'value2',
        ['value1', 'value3'],
      ],
      'remove non-existing value' => [
        ['value1', 'value3'],
        'value4',
        // No changes expected.
        ['value1', 'value3'],
      ],
    ];
  }

  /**
   * Tests the removeValues method.
   *
   * @dataProvider providerRemoveValues
   */
  public function testRemoveValues(array $array, array $values, array $expected): void {
    SchemaDotOrgArrayHelper::removeValues($array, $values);
    $this->assertSame($expected, $array);
  }

  /**
   * Data provider for testRemoveValues.
   */
  public static function providerRemoveValues(): array {
    return [
      'remove multiple values' => [
        ['value1', 'value2', 'value3', 'value4'],
        ['value2', 'value4'],
        ['value1', 'value3'],
      ],
      'remove non-matching values' => [
        ['value1', 'value2'],
        ['value4', 'value5'],
        // No changes expected.
        ['value1', 'value2'],
      ],
    ];
  }

}
