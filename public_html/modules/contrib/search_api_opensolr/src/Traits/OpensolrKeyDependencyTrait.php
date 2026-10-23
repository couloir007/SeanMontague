<?php

namespace Drupal\search_api_opensolr\Traits;

/**
 * Opensolr trait for managing optional key module dependency.
 *
 * The Key module is used ONLY when it is installed AND the site opted in via
 * the "use_key_module" setting on /admin/config/search/search-api/opensolr.
 * Merely installing Key (for some unrelated purpose) must never switch the
 * credential handling of this module (issue #3576419).
 */
trait OpensolrKeyDependencyTrait {

  /**
   * Gets the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   Returns the module handler service.
   */
  protected function moduleHandlerService() {
    return \Drupal::moduleHandler();
  }

  /**
   * Checks whether the Key module is installed.
   *
   * @return bool
   *   TRUE if the Key module is installed, FALSE otherwise.
   */
  protected function keyModuleAvailable(): bool {
    return $this->moduleHandlerService()->moduleExists('key');
  }

  /**
   * Checks whether the site opted in to the Key module integration.
   *
   * @return bool
   *   TRUE if "use_key_module" is enabled in the opensolr configuration.
   */
  protected function keyModuleEnabled(): bool {
    return (bool) \Drupal::config('search_api_opensolr.opensolrconfig')->get('use_key_module');
  }

  /**
   * Gets the key repository service whenever the Key module is installed.
   *
   * Ignores the opt-in flag: used where the caller decides itself whether the
   * Key integration applies (e.g. the config form's "Test connection").
   *
   * @return false|\Drupal\key\KeyRepositoryInterface
   *   The key repository service, or FALSE if the Key module is not installed.
   */
  protected function keyRepositoryIfInstalled() {
    return $this->keyModuleAvailable() ? \Drupal::service('key.repository') : FALSE;
  }

  /**
   * Gets the key repository service if the Key integration is in use.
   *
   * @return false|\Drupal\key\KeyRepositoryInterface
   *   Returns the key repository service if the Key module is installed AND
   *   enabled in the opensolr configuration, FALSE otherwise.
   */
  protected function keyRepositoryService() {
    return $this->keyModuleEnabled() ? $this->keyRepositoryIfInstalled() : FALSE;
  }

}
