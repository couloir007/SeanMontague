<?php

namespace Drupal\mercury_editor\Ajax;

use Drupal\Core\Ajax\OpenDialogCommand;

/**
 * Defines an AJAX command to open a Mercury dialog.
 *
 * This command extends OpenDialogCommand and is implemented in
 * Drupal.AjaxCommands.prototype.openMercuryDialog.
 */
class OpenMercuryDialogCommand extends OpenDialogCommand {

  /**
   * Returns an AJAX command to open a Mercury dialog.
   */
  public function render() {
    return [
      'command' => 'openMercuryDialog',
      'selector' => $this->selector,
      'settings' => $this->settings,
    ];
  }

}
