<?php

namespace Drupal\search_api_opensolr\Traits;

/**
 * Trait for common opensolr connector methods.
 */
trait OpenSolrConnectorTrait {

  /**
   * Gets an opensolr value by the given config name.
   *
   * @param string $configName
   *   The Drupal configuration name.
   * @param array $indexInfo
   *   An array containing the opensolr index info.
   *
   * @return array|int|mixed|string|string[]
   *   Returns the value if it exists, NULL otherwise.
   */
  protected function getConnectorValueByKey(string $configName, array $indexInfo) {
    $solrUrl = parse_url($indexInfo['connection_url']);
    return match ($configName) {
      'port' => isset($indexInfo['port']) && is_numeric($indexInfo['port']) ? (int) $indexInfo['port'] : $solrUrl['port'],
      'core' => str_replace('/solr/', '', $solrUrl['path']),
      'path' => '/',
      'username' => $indexInfo['auth_username'] ?? '',
      'password' => $indexInfo['auth_password'] ?? '',
      'index_type' => $indexInfo['type'],
      default => $solrUrl[$configName] ?? NULL,
    };
  }

  /**
   * Gets the keys of the form items that should be managed by OpenSolr.
   *
   * @return string[]
   *   Returns an array of Drupal keys.
   */
  protected function getOpenSolrConfigs() {
    return [
      'scheme',
      'host',
      'port',
      'core',
      'path',
      'username',
      'password',
    ];
  }

}
