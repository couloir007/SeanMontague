<?php

namespace Drupal\flags;

/**
 * Interface for language manager that provides extended language information.
 *
 * The purpose of this service is to provide features that are missing from
 * ConfigurableLanguageManager.
 *
 * @todo Consider extending \Drupal\language\ConfigurableLanguageManager and
 * replacing language_manager service.
 *
 * @package Drupal\flags
 */
interface FullLanguageManagerInterface {

  /**
   * Returns list of ALL languages including predefined and configured.
   *
   * @return array
   *   Array of language codes and names.
   */
  public function getAllDefinedLanguages();

}
