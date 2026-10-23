<?php

namespace Drupal\search_api_opensolr;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Prevents uninstalling the Key module while opensolr stores its key there.
 *
 * Uninstalling Key would delete the Key entity that holds the opensolr API
 * key, silently breaking every opensolr server. The user has to switch the
 * opensolr configuration back to direct storage first.
 */
class KeyModuleUninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /**
   * Constructs the validator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory, to read the opensolr settings.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module === 'key' && $this->configFactory->get('search_api_opensolr.opensolrconfig')->get('use_key_module')) {
      $reasons[] = $this->t('Search API opensolr stores its API key with the Key module. <a href=":url">Switch it to direct credential storage</a> before uninstalling Key.', [
        ':url' => Url::fromRoute('search_api_opensolr.opensolr_config_form')->toString(),
      ]);
    }
    return $reasons;
  }

}
