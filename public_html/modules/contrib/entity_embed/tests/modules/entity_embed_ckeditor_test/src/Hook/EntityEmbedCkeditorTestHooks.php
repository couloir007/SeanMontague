<?php

declare(strict_types=1);

namespace Drupal\entity_embed_ckeditor_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_embed_ckeditor_test.
 */
class EntityEmbedCkeditorTestHooks {

  /**
   * Implements hook_editor_info_alter().
   */
  #[Hook('editor_info_alter')]
  public function editorInfoAlter(array &$editors): void {
    // Drupal 9 used to have an editor called ckeditor. Copy the Unicorn editor
    // to it to be able to test upgrading to CKEditor 5.
    $editors['ckeditor'] = $editors['unicorn'];
  }

}
