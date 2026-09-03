<?php

namespace Drupal\geolocation\Ajax;

use Drupal\Core\Ajax\InsertCommand;

/**
 * Class ExtendCommand.
 */
class Geolocation extends InsertCommand {

  /**
   * {@inheritDoc}
   */
  public function render(): array {
    return [
      'command' => 'geolocation',
      'method' => 'replaceCommonMapView',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
