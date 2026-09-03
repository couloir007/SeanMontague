<?php

namespace Drupal\flags\Mapping;

/**
 * Maps language code to country/territory code.
 */
class Language extends BaseMapping {

  /**
   * {@inheritdoc}
   */
  protected $extraClasses = ['flag-lang'];

  /**
   * {@inheritDoc}
   */
  protected function getConfigKey() {
    return 'flags.language_flag_mapping';
  }

}
