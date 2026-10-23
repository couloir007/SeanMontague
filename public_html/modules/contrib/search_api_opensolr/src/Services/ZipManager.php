<?php

namespace Drupal\search_api_opensolr\Services;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrConfigFiles;
use Drupal\search_api_solr\Controller\SolrConfigSetController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Service class for creating and uploading the zip on OpenSolr.
 */
class ZipManager {
  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * The OpenSolrConfigFiles service property.
   *
   * @var \Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrConfigFiles
   */
  protected $configFiles;

  /**
   * The FileSystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The PrivateTempStore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStore;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The Search API opensolr logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  public function __construct(OpenSolrConfigFiles $configFiles, FileSystemInterface $fileSystem, PrivateTempStoreFactory $privateTempStoreFactory, MessengerInterface $messenger, ModuleExtensionList $module_extension_list, LoggerChannelInterface $loggerChannel) {
    $this->configFiles = $configFiles;
    $this->fileSystem = $fileSystem;
    $this->privateTempStore = $privateTempStoreFactory;
    $this->messenger = $messenger;
    $this->moduleExtensionList = $module_extension_list;
    $this->loggerChannel = $loggerChannel;
  }

  /**
   * Imports the zip automatically when a server is created.
   *
   * @param \Drupal\search_api\ServerInterface $searchApiServer
   *   The currently created server.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect user to the server page.
   */
  public function importConfigZip(ServerInterface $searchApiServer) {
    try {
      // @todo Not sure if this is a valid approach instead of extending the controller.
      $solrConfigSetController = new SolrConfigSetController($this->moduleExtensionList);
      $solrConfigSetController->setServer($searchApiServer);

      $solrConfigFiles = $solrConfigSetController->getConfigFiles();
      $zipFileName = uniqid('opensolrZipConfig-') . '.zip';
      $zipFilePath = $this->fileSystem->realpath('temporary://' . $zipFileName);
      $this->processZip($solrConfigFiles, $zipFilePath);

      $configuration = $searchApiServer->getBackendConfig();
      $result = $this->configFiles->uploadZipConfigFiles($configuration['connector_config']['core'], $zipFilePath);

      $this->fileSystem->unlink('temporary://' . $zipFileName);
      $this->processResult($result, $searchApiServer->id());
    }
    catch (SearchApiException $e) {
      Error::logException($this->loggerChannel, $e);
    }

    return new RedirectResponse($searchApiServer->toUrl('canonical')->toString());
  }

  /**
   * Processes the solr config files to match opensolr requirements.
   *
   * @param array $solrConfigFiles
   *   An associative array of files names and content.
   * @param string $zipFilePath
   *   The zip file real path.
   */
  protected function processZip(array $solrConfigFiles, $zipFilePath) {
    $zip = new \ZipArchive();
    if ($zip->open($zipFilePath, \ZipArchive::CREATE) === TRUE) {
      foreach ($solrConfigFiles as $name => $content) {
        // Make sure that solrconfig.xml file does not contain <updateLog>
        // tags.
        if ($name == 'solrconfig.xml') {
          $content = preg_replace('/(\<updateLog\>.*\<\/updateLog\>)/s', '', $content);
        }
        $zip->addFromString($name, $content);
      }
      $zip->close();
    }
  }

  /**
   * Process the result and show the messages on the page.
   *
   * @param array $result
   *   The result from the POST request.
   * @param string $serverId
   *   The server id to create config zip import url.
   */
  protected function processResult(array $result, $serverId) {
    if (!empty($result)) {
      if (isset($result['status']) && !$result['status']) {
        // The API answers with "msg", our own error responses carry the same
        // text in "msg"/"error"; never hand NULL to the logger (TypeError).
        $this->loggerChannel->error('Config zip upload failed: @msg', [
          '@msg' => (string) ($result['msg'] ?? $result['error'] ?? 'unknown error'),
        ]);
        $error = $this->t('An error occurred while trying to upload the configuration zip. Try uploading the zip file again from <a href="@link">here</a>.', [
          '@link' => Url::fromRoute('search_api_opensolr.opensolr_config_zip_import', ['search_api_server' => $serverId])->toString(),
        ]);
      }
      else {
        if (!empty($result['msg']) && is_array($result['msg'])) {
          $msg = $result['msg'];
          if (isset($msg['Files upload']) && $msg['Files upload'] == 'OK') {
            unset($msg['Files upload']);
            $processed[] = 'config.zip';
          }
          foreach ($msg as $file => $status) {
            if (is_string($status) && strpos($status, 'OK') == 11) {
              $processed[] = $file;
            }
            else {
              $errors[] = $file;
            }
          }
        }
        if (!empty($errors)) {
          try {
            $this->privateTempStore->get('search_api_opensolr')->set('invalid_files', $errors);
          }
          catch (TempStoreException $e) {
            Error::logException($this->loggerChannel, $e);
          }
          $error = $this->t('The following files were not successfully uploaded: @files. You can import these files again <a href="@link">here</a>.', [
            '@files' => implode(', ', $errors),
            '@link' => Url::fromRoute('search_api_opensolr.opensolr_config_files_import', ['search_api_server' => $serverId])->toString(),
          ]);
        }
        if (!empty($processed)) {
          $status = $this->t('The following files were uploaded successfully to Opensolr: @files.', [
            '@files' => implode(', ', $processed),
          ]);
        }
      }
    }
    else {
      $error = $this->t('An error occurred while trying to upload the configuration zip. Try uploading the zip file again from <a href="@link">here</a>.', [
        '@link' => Url::fromRoute('search_api_opensolr.opensolr_config_zip_import', ['search_api_server' => $serverId])->toString(),
      ]);
    }

    // Show the message on the page.
    if (!empty($error)) {
      $this->messenger->addError($error);
    }
    if (!empty($status)) {
      $this->messenger->addStatus($status);
    }
  }

}
