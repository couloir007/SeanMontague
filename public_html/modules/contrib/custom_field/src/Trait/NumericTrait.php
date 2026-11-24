<?php

namespace Drupal\custom_field\Trait;

/**
 * Trait for various numeric methods.
 */
trait NumericTrait {

  /**
   * Helper method to get the min value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int|float
   *   The minimum value allowed by the database.
   */
  protected static function getDefaultMinValue(array $settings): int|float {
    if (!empty($settings['unsigned'])) {
      return 0;
    }

    // Each value is - (2 ^ (8 * bytes - 1)).
    $size_map = [
      'normal' => -2147483648,
      'tiny' => -128,
      'small' => -32768,
      'medium' => -8388608,
      'big' => -9223372036854775808,
    ];
    $size = $settings['size'] ?? 'normal';

    return $size_map[(string) $size];
  }

  /**
   * Helper method to get the max value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int
   *   The maximum value allowed by the database.
   */
  protected static function getDefaultMaxValue(array $settings): int {
    if (!empty($settings['unsigned'])) {
      // Each value is (2 ^ (8 * bytes) - 1).
      $size_map = [
        'normal' => 4294967295,
        'tiny' => 255,
        'small' => 65535,
        'medium' => 16777215,
        'big' => PHP_INT_MAX,
      ];
    }
    else {
      // Each value is (2 ^ (8 * bytes - 1) - 1).
      $size_map = [
        'normal' => 2147483647,
        'tiny' => 127,
        'small' => 32767,
        'medium' => 8388607,
        'big' => PHP_INT_MAX,
      ];
    }
    $size = $settings['size'] ?? 'normal';

    return $size_map[(string) $size];
  }

  /**
   * Helper method to truncate a decimal number to a given number of decimals.
   *
   * @param float $decimal
   *   Decimal number to truncate.
   * @param int $num
   *   Number of digits the output will have.
   *
   * @return float
   *   Decimal number truncated.
   */
  protected static function truncateDecimal(float $decimal, int $num): float {
    $factor = pow(10, $num);
    return floor($decimal * $factor) / $factor;
  }

  /**
   * Helper method to get the number of decimal digits out of a decimal number.
   *
   * @param float|int $decimal
   *   The number to calculate the number of decimals digits from.
   *
   * @return int
   *   The number of decimal digits.
   */
  protected static function getDecimalDigits(float|int $decimal): int {
    $digits = 0;
    while ($decimal - round($decimal)) {
      $decimal *= 10;
      $digits++;
    }

    return $digits;
  }

}
