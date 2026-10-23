<?php

namespace Drupal\search_api_opensolr\OpenSolrApi\Components;

use Drupal\search_api_opensolr\OpenSolrApi\OpenSolrBase;

/**
 * Defines the opensolr config files API component.
 *
 * @todo Improve the way we handle the responses.
 *
 * @package Drupal\search_api_opensolr\OpenSolrApi\Components
 */
class OpenSolrConfigFiles extends OpenSolrBase implements OpenSolrConfigFilesInterface {

  /**
   * {@inheritdoc}
   */
  public function getAllConfigFiles($coreName) {
    $params = ['core_name' => $coreName];
    // @todo Implement the response.
    return $this->apiCall('/get_all_config_files', 'GET', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function uploadZipConfigFiles($coreName, $zipFile) {
    $params = [
      'core_name' => $coreName,
      'userfile' => $zipFile,
    ];
    return $this->apiCall('/upload_zip_config_files', 'POST', $params, FALSE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function uploadConfigFile($coreName, $userFile) {
    $params = [
      'core_name' => $coreName,
      'userfile' => 'public://' . $userFile,
    ];
    return $this->apiCall('/upload_config_file', 'POST', $params, FALSE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteConfigFile($coreName, $fileName, $fileExtension) {
    $params = [
      'core_name' => $coreName,
      'file_name' => $fileName,
      'file_extension' => $fileExtension,
    ];
    // @todo Implement the response.
    return $this->apiCall('/delete_config_file', 'GET', $params);
  }

}
