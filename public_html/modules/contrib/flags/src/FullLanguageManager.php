<?php

namespace Drupal\flags;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides extended language manager with all defined languages.
 */
class FullLanguageManager implements FullLanguageManagerInterface {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FullLanguageManager constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllDefinedLanguages() {
    // Get list of all configured languages.
    $languages = [];

    // See ConfigurableLanguageManager::getLanguages() for details.
    $predefined = LanguageManager::getStandardLanguageList();

    foreach ($predefined as $key => $value) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $languages[$key] = new TranslatableMarkup($value[0]);
    }

    $config_ids = $this->configFactory->listAll('language.entity.');
    foreach ($this->configFactory->loadMultiple($config_ids) as $config) {
      $data = $config->get();
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $languages[$data['id']] = new TranslatableMarkup($data['label']);
    }

    asort($languages);
    return $languages;
  }

}
