<?php

namespace Drupal\flags\Mapping;

/**
 * Maps country code to country/territory code.
 */
class Country extends BaseMapping {

  /**
   * {@inheritdoc}
   */
  protected $extraClasses = ['country-flag'];

  /**
   * {@inheritDoc}
   */
  protected function getConfigKey() {
    return 'flags.country_flag_mapping';
  }

}
