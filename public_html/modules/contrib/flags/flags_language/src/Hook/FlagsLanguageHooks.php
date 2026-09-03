<?php

namespace Drupal\flags_language\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for flags_language.
 */
class FlagsLanguageHooks {

  /**
   * Implements hook_language_switch_links_alter().
   *
   * Implemented to add flags to the language switcher links.
   */
  #[Hook('language_switch_links_alter')]
  public static function languageSwitchLinksAlter(array &$links, $type, $path) {
    /** @var \Drupal\flags\Mapping\FlagMappingInterface $mapper */
    $mapper = \Drupal::service('flags.mapping.language');
    foreach ($links as $langCode => &$link) {
      $title = $link['title'];
      // If title is a string, then we turn it into renderable array.
      // Otherwise it probably already is a renderable array.
      if (is_string($title)) {
        $title = [
          '#markup' => $link['title'],
        ];
      }
      $link['title'] = [
        'flag' => [
          '#theme' => 'flags',
          '#code' => $mapper->map($langCode),
          '#source' => 'language',
        ],
        'title' => $title,
      ];
    }
  }

  /**
   * Implements hook_block_view_BASE_BLOCK_ID_alter().
   *
   * Implemented to attach flags CSS to the language switcher block.
   */
  #[Hook('block_view_language_block_alter')]
  public static function blockViewLanguageBlockAlter(array &$build, BlockPluginInterface $block) {
    $build['#attached']['library'][] = 'flags/flags';
  }

}
