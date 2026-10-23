<?php

namespace Drupal\search_api_opensolr\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\file\FileInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrConfigFiles;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for OpenSolr config files upload.
 */
class OpenSolrConfigFilesImport extends FormBase {

  /**
   * PrivateTempStore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * OpenSolrConfigFiles service.
   *
   * @var \Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrConfigFiles
   */
  protected $configFiles;

  /**
   * EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FileSystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Search API opensolr logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  public function __construct(PrivateTempStoreFactory $privateTempStoreFactory, OpenSolrConfigFiles $configFiles, EntityTypeManagerInterface $entityTypeManager, FileSystemInterface $fileSystem, LoggerChannelInterface $loggerChannel) {
    $this->tempStore = $privateTempStoreFactory;
    $this->configFiles = $configFiles;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->loggerChannel = $loggerChannel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('search_api_opensolr.client_config_files'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.channel.search_api_opensolr')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'open_solr_config_files_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?ServerInterface $search_api_server = NULL) {
    // The form fields should appear only if the user has set its OpenSolr
    // credentials.
    $credentials = $this->config('search_api_opensolr.opensolrconfig')->get('opensolr_credentials');
    if (!empty($credentials)) {
      $files = $this->tempStore->get('search_api_opensolr')->get('invalid_files');
      if (!empty($files)) {
        $form['#tree'] = TRUE;
        $configuration = $search_api_server->getBackendConfig();
        $form['core'] = [
          '#type' => 'hidden',
          '#value' => $configuration['connector_config']['core'],
        ];
        $form['files'] = [
          '#type' => 'container',
          '#title' => $this->t('Files'),
        ];
        foreach ($files as $file) {
          $form['files'][$file] = [
            '#type' => 'managed_file',
            '#title' => $file,
            '#upload_location' => 'public://',
            '#upload_validators' => [
              'FileExtension' => [
                'extensions' => substr($file, strrpos($file, '.') + 1),
              ],
            ],
          ];
        }
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Upload'),
        ];
      }
      else {
        $form['#markup'] = $this->t('Couln\'t find invalid files to import. If you think your configuration did not import properly, reimport the full zip <a href="@link">here</a>.', [
          '@link' => Url::fromRoute('search_api_opensolr.opensolr_config_zip_import', ['search_api_server' => $search_api_server->id()])->toString(),
        ]);
      }
    }
    else {
      $form['#markup'] = $this->t('You have to set OpenSolr <a href="@credentials">credentials</a> before uploading a config zip.', [
        '@credentials' => Url::fromRoute('search_api_opensolr.opensolr_config_form')->toString(),
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty the files from the temp store.
    $this->tempStore->get('search_api_opensolr')->delete('invalid_files');

    $files = $form_state->getValue('files');
    $core = $form_state->getValue('core');
    if (!empty($files)) {
      $results = [];
      foreach ($files as $fileId) {
        if (!empty($fileId)) {
          $file = $this->entityTypeManager->getStorage('file')->load($fileId[0]);
          if ($file instanceof FileInterface) {
            $this->processConfigFile($file);
            $results[$file->getFilename()] = $this->configFiles->uploadConfigFile($core, $file->getFilename());
            try {
              $file->delete();
            }
            catch (EntityStorageException $e) {
              Error::logException($this->loggerChannel, $e);
            }
          }
        }
      }
      $this->processResult($results);
    }
  }

  /**
   * Remove the updateLog tags from the solr config file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file object.
   */
  protected function processConfigFile(FileInterface $file) {
    $path = $this->fileSystem->realpath($file->getFileUri());
    $content = file_get_contents($path);
    $newFile = preg_replace('/(\<updateLog\>.*\<\/updateLog\>)/s', '', $content);
    file_put_contents($path, $newFile);
  }

  /**
   * Process the result and show the messages on the page.
   *
   * @param array $results
   *   An array containing the result from each file upload request.
   */
  protected function processResult(array $results) {
    foreach ($results as $file => $result) {
      if (empty($result)) {
        $errors[] = $file;
      }
      else {
        if ($result['status'] == FALSE) {
          $this->loggerChannel->error($result['msg']);
          $errors[] = $file;
        }
        else {
          $status[] = $file;
        }
      }
    }
    if (!empty($errors)) {
      $this->getStringTranslation()->formatPlural(count($errors), 'An error occurred while importing this file: @files.', 'An error occurred while importing those files: @files.', [
        '@files' => implode(', ', $errors),
      ]);
    }
    if (!empty($status)) {
      $this->getStringTranslation()->formatPlural(count($status), 'The following file was imported successfully: @files.', 'The following files were imported successfully: @files.', [
        '@files' => implode(', ', $status),
      ]);
    }
  }

}
