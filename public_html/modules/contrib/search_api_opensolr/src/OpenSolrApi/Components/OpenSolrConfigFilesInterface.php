<?php

namespace Drupal\search_api_opensolr\OpenSolrApi\Components;

/**
 * Provides an interface defining an OpenSolrConfigFiles component.
 *
 * @package Drupal\search_api_opensolr\OpenSolrApi\Components
 */
interface OpenSolrConfigFilesInterface {

  /**
   * Gets all the configuration files list from the server.
   *
   * @param string $coreName
   *   The core name (id of the index).
   */
  public function getAllConfigFiles($coreName);

  /**
   * Uploads the zip archive containing the solr configuration files.
   *
   * @param string $coreName
   *   The core name (id of the index).
   * @param mixed $zipFile
   *   The zip file to upload to the server.
   *
   * @return array
   *   Return the response of the POST request.
   */
  public function uploadZipConfigFiles($coreName, $zipFile);

  /**
   * Uploads or updates a custom individual configuration file for a SOLR core.
   *
   * @param string $coreName
   *   The core name (id of the index).
   * @param string $userFile
   *   The local schema.xml file to upload to the server.
   *
   * @return array
   *   Return the response of the POST request.
   */
  public function uploadConfigFile($coreName, $userFile);

  /**
   * Deletes an index configuration file from the server.
   *
   * @param string $coreName
   *   The core name (id of the index).
   * @param string $fileName
   *   The name of the file without extension.
   * @param string $fileExtension
   *   The extension of the file.
   */
  public function deleteConfigFile($coreName, $fileName, $fileExtension);

}
