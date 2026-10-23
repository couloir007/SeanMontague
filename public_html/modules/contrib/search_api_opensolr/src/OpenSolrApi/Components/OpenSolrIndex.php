<?php

namespace Drupal\search_api_opensolr\OpenSolrApi\Components;

use Drupal\search_api_opensolr\OpenSolrApi\OpenSolrBase;

/**
 * Defines the opensolr index API component.
 *
 * @todo Improve the way we handle the responses.
 *
 * @package Drupal\search_api_opensolr\OpenSolrApi\Components
 */
class OpenSolrIndex extends OpenSolrBase implements OpenSolrIndexInterface {

  /**
   * {@inheritdoc}
   */
  public function getIndexList(array $params = [], $excludeReplicas = TRUE) : array {
    $result = [];

    $indexes = $this->apiCall('/get_index_list', 'GET', $params);
    if (is_array($indexes) && !isset($indexes['status'])) {
      foreach ($indexes as $index) {
        if ($excludeReplicas && $index['index_type'] > 0) {
          continue;
        }
        $result[$index['index_name']] = $index['index_name'];
      }

      return $result;
    }

    return $indexes ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreInfo($coreName) {
    $result = [];

    $params = ['core_name' => $coreName];
    $coreInfo = $this->apiCall('/get_core_info', 'GET', $params);
    if (isset($coreInfo['status']) && $coreInfo['status'] === TRUE) {
      $result = !empty($coreInfo['msg']) && is_array($coreInfo['msg']) ? $coreInfo['msg'] : [];
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function reloadCore($coreName) {
    $params = ['core_name' => $coreName];
    $coreReload = $this->apiCall('/reload_core', 'GET', $params);
    if (isset($coreReload['status']) && $coreReload['status'] === TRUE) {
      return TRUE;
    }

    return $coreReload;
  }

  /**
   * {@inheritdoc}
   */
  public function optimizeCore($coreName) {
    $params = ['core_name' => $coreName];
    $coreOptimize = $this->apiCall('/optimize', 'GET', $params);
    if (isset($coreOptimize['status']) && $coreOptimize['status'] === TRUE) {
      return TRUE;
    }

    return $coreOptimize;
  }

  /**
   * {@inheritdoc}
   */
  public function commitData($coreName) {
    $params = ['core_name' => $coreName];
    $coreCommit = $this->apiCall('/commit', 'GET', $params);
    if (isset($coreCommit['status']) && $coreCommit['status'] === TRUE) {
      return TRUE;
    }

    return $coreCommit;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreStatus($coreName) {
    $params = ['core_name' => $coreName];
    $coreStatus = $this->apiCall('/get_core_status', 'GET', $params);
    if (isset($coreStatus['status']) && $coreStatus['status'] === TRUE) {
      return $this->json->decode($coreStatus['msg']);
    }

    return $coreStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function createCore($coreName, $region) {
    $params = [
      'index_name' => $coreName,
      'region' => $region,
    ];
    return $this->apiCall('/create_core', 'GET', $params, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCore($coreName) {
    $params = ['core_name' => $coreName];
    $coreDelete = $this->apiCall('/delete_core', 'GET', $params);
    if (isset($coreDelete['status']) && $coreDelete['status'] === TRUE) {
      return TRUE;
    }

    return $coreDelete;
  }

  /**
   * {@inheritdoc}
   */
  public function replicateIndex($coreName, $targetIndex) {
    // @todo Need to wait for API update.
    $params = ['core_name' => $coreName, 'target_index' => $targetIndex];
    return $this->apiCall('/replicate_index', 'GET', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironments() {
    return $this->apiCall('/get_env', 'GET', [], TRUE);
  }

}
