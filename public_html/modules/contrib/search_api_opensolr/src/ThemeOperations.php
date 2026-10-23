<?php

namespace Drupal\search_api_opensolr;

/**
 * Handles hooks related to theme.
 */
class ThemeOperations {

  /**
   * Implements hook_theme().
   *
   * @see \search_api_opensolr_theme()
   */
  public function theme($existing, $type, $theme, $path) {
    return [
      'opensolr_help_autoconfigure' => [
        'variables' => [],
      ],
      'opensolr_get_started' => [
        'variables' => [
          'steps' => [],
        ],
      ],
    ];
  }

}
