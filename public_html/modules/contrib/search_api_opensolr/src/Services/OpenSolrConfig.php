<?php

namespace Drupal\search_api_opensolr\Services;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\key\Entity\Key;
use Drupal\key\KeyInterface;
use Drupal\search_api_opensolr\Traits\OpensolrKeyDependencyTrait;

/**
 * Retrieves and processes the global opensolr configuration.
 */
class OpenSolrConfig {
  use OpensolrKeyDependencyTrait;

  /**
   * The opensolr editable configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $configEditable;

  /**
   * The opensolr immutable configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The key repository service if the key module is installed.
   *
   * @var false|\Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepositoryService;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configEditable = $config_factory->getEditable('search_api_opensolr.opensolrconfig');
    $this->config = $config_factory->get('search_api_opensolr.opensolrconfig');
    $this->keyRepositoryService = $this->keyRepositoryService();
  }

  /**
   * Sets the opensolr email used in API calls.
   *
   * @param string $email
   *   The opensolr email.
   */
  public function setEmail(string $email): void {
    $credentials = $this->configEditable->get('opensolr_credentials');
    $credentials['email'] = $email;
    $this->configEditable->set('opensolr_credentials', $credentials);
    $this->configEditable->save();
  }

  /**
   * Gets the opensolr email from the Drupal configuration.
   *
   * @return string
   *   The opensolr email.
   */
  public function getEmail(): string {
    $config = $this->config->get('opensolr_credentials');
    return $config['email'] ?? '';
  }

  /**
   * Sets the opensolr API key.
   *
   * If the Key module exists, we generate a new default key with the API key as
   * value, otherwise we set the raw key in the configuration.
   *
   * @param string $apiKey
   *   The API key.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setApiKey(string $apiKey): void {
    $credentials = $this->configEditable->get('opensolr_credentials');
    if ($this->keyRepositoryService) {
      $key = $this->createKey($apiKey);
      $credentials['api_key'] = $key->id();
    }
    else {
      $credentials['api_key_raw'] = $apiKey;
    }
    $this->configEditable->set('opensolr_credentials', $credentials);
    $this->configEditable->save();
  }

  /**
   * Gets the opensolr API key from the Drupal configuration.
   *
   * @return string
   *   The opensolr API key.
   */
  public function getApiKey(): string {
    $credentials = $this->getApiCredentials();
    return $credentials['api_key'] ?? '';
  }

  /**
   * Gets the API credentials from the stored configuration.
   *
   * @return array
   *   Returns an array with the email and api_key keys and values.
   */
  public function getApiCredentials(): array {
    return $this->extractApiCredentials($this->config->get('opensolr_credentials'));
  }

  /**
   * Extracts the API credentials from the given array.
   *
   * @param array $credentials
   *   An array of credentials that should contain the email, the api_key and
   *   the api_key_raw. The api_key holds a Key entity ID when the Key module
   *   integration is in use, api_key_raw holds the plain API key otherwise.
   * @param bool|null $useKey
   *   Whether to resolve api_key through the Key module. NULL (default) means
   *   "whatever the saved opensolr configuration says"; the config form passes
   *   the (possibly not yet saved) checkbox value here so "Test connection"
   *   honours what the user is about to save.
   *
   * @return array
   *   Returns an array with the email and the api_key (always the plain key).
   */
  public function extractApiCredentials(array $credentials, ?bool $useKey = NULL): array {
    $useKey = $useKey ?? (bool) $this->keyRepositoryService;
    $resolved = FALSE;
    $repository = $useKey ? $this->keyRepositoryIfInstalled() : FALSE;
    if ($repository && !empty($credentials['api_key'])) {
      $key = $repository->getKey($credentials['api_key']);
      if ($key instanceof KeyInterface) {
        $credentials['api_key'] = $key->getKeyValue();
        $resolved = TRUE;
      }
    }
    if (!$resolved) {
      // Direct storage, or Key integration selected but the referenced key does
      // not exist (e.g. deleted): fall back to the raw key so an existing setup
      // keeps working instead of failing with an empty credential.
      $credentials['api_key'] = $credentials['api_key_raw'] ?? '';
    }
    unset($credentials['api_key_raw']);
    return $credentials;
  }

  /**
   * Generates a key that contains the opensolr API key.
   *
   * @param string $value
   *   The opensolr API key.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\key\Entity\Key
   *   Returns the newly created Key object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createKey(string $value): EntityInterface|Key {
    $key = Key::create([
      'id' => uniqid('opensolr_'),
      'label' => 'opensolr auto key',
      'description' => 'Key generated automatically for opensolr configuration',
      'key_type' => 'authentication',
      'key_provider' => 'config',
      'key_provider_settings' => [
        'key_value' => $value,
      ],
      'key_input' => 'text_field',
    ]);
    $key->save();
    return $key;
  }

}
