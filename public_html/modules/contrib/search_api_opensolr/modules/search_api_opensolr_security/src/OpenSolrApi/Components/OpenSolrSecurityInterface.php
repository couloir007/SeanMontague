<?php

namespace Drupal\search_api_opensolr_security\OpenSolrApi\Components;

/**
 * Provides an interface defining an OpenSolrSecurity component.
 *
 * @package Drupal\search_api_opensolr_security\OpenSolrApi\Components
 */
interface OpenSolrSecurityInterface {

  /**
   * Updates the HTTP Auth for the given OpenSolr Index.
   *
   * @param string $coreName
   *   The core name (index id).
   * @param string $username
   *   THe HTTP Auth username.
   * @param string $password
   *   The HTTP Auth password.
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   Returns a new OpenSolrResponse object.
   */
  public function updateHttpAuth($coreName, $username, $password);

  /**
   * Removes the HTTP Auth for the given OpenSolr Index.
   *
   * @param string $coreName
   *   The core name (index id).
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   Returns a new OpenSolrResponse object.
   */
  public function removeHttpAuth($coreName);

  /**
   * Gets the IP list for the given SOLR core.
   *
   * @param string $coreName
   *   The core name (index id).
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   Returns a new OpenSolrResponse object.
   */
  public function getIpList($coreName);

  /**
   * Adds IP access rule for the given SOLR core.
   *
   * @param string $coreName
   *   The core name (index id).
   * @param string $ip
   *   The IP address that will be granted access.
   * @param string $handler
   *   The SOLR URI handler that the restriction will be applied to.
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   Returns a new OpenSolrResponse object.
   */
  public function addIp($coreName, $ip, $handler);

  /**
   * Removes IP access rule for the given SOLR core.
   *
   * @param string $coreName
   *   The core name (index id).
   * @param string $ip
   *   The IP address that will be granted access.
   * @param string $handler
   *   The SOLR URI handler that the restriction will be applied to.
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   Returns a new OpenSolrResponse object.
   */
  public function removeIp($coreName, $ip, $handler);

}
