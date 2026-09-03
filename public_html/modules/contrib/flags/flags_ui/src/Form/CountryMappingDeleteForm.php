<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Url;

/**
 * Provides a form for deleting country flag mapping entities.
 */
class CountryMappingDeleteForm extends FlagMappingDeleteForm {

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl() {
    return new Url('entity.country_flag_mapping.list');
  }

}
