<?php

namespace Drupal\mercury_editor\Entity;

use Drupal\node\NodeForm;

/**
 * Node form for mercury editor edit tray.
 */
class MercuryEditorNodeForm extends NodeForm implements MercuryEditorEntityFormInterface {

  use MercuryEditorEntityFormTrait;

  /**
   * {@inheritDoc}
   */
  public function setDefaultEntityValues() {
    $this->entity->in_preview = TRUE;
    $this->entity->title = $this->t('New :type', [':type' => $this->entity->type->entity->label()]);
    $this->entity->preview_view_mode = 'full';
  }

}
