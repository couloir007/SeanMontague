<?php

namespace Drupal\mercury_editor\Entity;

use Drupal\taxonomy\TermForm;

/**
 * Defines the Mercury Editor term form.
 */
class MercuryEditorTermForm extends TermForm {

  use MercuryEditorEntityFormTrait;

  /**
   * {@inheritDoc}
   */
  public function setDefaultEntityValues() {
    $this->entity->name = 'New term';
  }

}
