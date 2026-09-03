<?php

namespace Drupal\flags\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for flags.
 */
class FlagsHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme($existing, $type, $theme, $path) {
    return [
      'flags' => [
        'variables' => [
          'code' => NULL,
          'source' => $type,
          'tag' => 'span',
          'attributes' => [],
        ],
      ],
    ];
  }

}
