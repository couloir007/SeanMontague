<?php

namespace Drupal\search_api_opensolr_security\OpenSolrApi\Components;

use Drupal\search_api_opensolr\OpenSolrApi\OpenSolrBase;

/**
 * Defines the opensolr security API component.
 *
 * @package Drupal\search_api_opensolr_security\OpenSolrApi\Components
 */
class OpenSolrSecurity extends OpenSolrBase implements OpenSolrSecurityInterface {

  /**
   * {@inheritdoc}
   */
  public function updateHttpAuth($coreName, $username, $password) {
    $params = [
      'core_name' => $coreName,
      'username' => $username,
      'password' => $password,
    ];
    return $this->apiCall('/update_http_auth', 'GET', $params, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function removeHttpAuth($coreName) {
    $params = ['core_name' => $coreName];
    return $this->apiCall('/remove_http_auth', 'GET', $params, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getIpList($coreName) {
    $params = ['core_name' => $coreName];
    return $this->apiCall('/get_ip_list', 'GET', $params, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function addIp($coreName, $ip, $handler) {
    $params = [
      'core_name' => $coreName,
      'ip' => $ip,
      'handler' => $handler,
    ];
    return $this->apiCall('/add_ip', 'GET', $params, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function removeIp($coreName, $ip, $handler) {
    $params = [
      'core_name' => $coreName,
      'ip' => $ip,
      'handler' => $handler,
    ];
    return $this->apiCall('/delete_ip', 'GET', $params, TRUE);
  }

}
