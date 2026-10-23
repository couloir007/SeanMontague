<?php

namespace Drupal\search_api_opensolr\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\file\FileInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrConfigFilesInterface;
use Drupal\search_api_opensolr\Services\OpenSolrConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for OpenSolr config zip upload.
 */
class OpenSolrConfigZipImport extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OpenSolr config files service.
   *
   * @var \Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrConfigFilesInterface
   */
  protected $configFiles;

  /**
   * PrivateTempStore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStore;

  /**
   * FileSystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The opensolr config service.
   *
   * @var \Drupal\search_api_opensolr\Services\OpenSolrConfig
   */
  protected OpenSolrConfig $openSolrConfig;

  /**
   * The uploaded Zip File if successful.
   *
   * @var null|\Drupal\file\FileInterface
   */
  protected FileInterface|null $zipFile;

  /**
   * The Search API opensolr logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'open_solr_config_zip_import';
  }

  public function __construct(EntityTypeManagerInterface $entityTypeManager, OpenSolrConfigFilesInterface $configFiles, PrivateTempStoreFactory $privateTempStoreFactory, FileSystemInterface $fileSystem, OpenSolrConfig $openSolrConfig, LoggerChannelInterface $loggerChannel) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFiles = $configFiles;
    $this->privateTempStore = $privateTempStoreFactory;
    $this->fileSystem = $fileSystem;
    $this->openSolrConfig = $openSolrConfig;
    $this->loggerChannel = $loggerChannel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('search_api_opensolr.client_config_files'),
      $container->get('tempstore.private'),
      $container->get('file_system'),
      $container->get('search_api_opensolr.config'),
      $container->get('logger.channel.search_api_opensolr'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?ServerInterface $search_api_server = NULL) {
    // The form fields should appear only if the user has set its OpenSolr
    // credentials.
    $credentials = $this->openSolrConfig->getApiCredentials();
    if (!empty($credentials)) {
      $configuration = $search_api_server->getBackendConfig();
      $form['core'] = [
        '#type' => 'hidden',
        '#value' => $configuration['connector_config']['core'],
      ];
      $form['server_id'] = [
        '#type' => 'hidden',
        '#value' => $search_api_server->id(),
      ];
      $form['config_zip'] = [
        '#type' => 'file',
        '#title' => $this->t('Config Zip'),
        '#description' => $this->t('If you do not have a config zip already, download it from <a href="@link">here</a>.', [
          '@link' => Url::fromRoute('solr_configset.config_zip', ['search_api_server' => $search_api_server->id()])->toString(),
        ]),
        '#required' => TRUE,
        '#upload_validators' => [
          'FileExtension' => [
            'extensions' => 'zip',
          ],
        ],
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Upload config zip'),
      ];
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $validators = [
      'FileExtension' => [
        'extensions' => 'zip',
      ],
    ];
    $sourceFile = file_save_upload('config_zip', $validators, FALSE, 0);
    if (!empty($sourceFile)) {
      $this->zipFile = $sourceFile;
    }
    else {
      $form_state->setErrorByName('config_zip');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->processConfigFile($this->zipFile);
    $result = $this->configFiles->uploadZipConfigFiles($values['core'], $this->zipFile->getFileUri());
    $this->processResult($result, $values['server_id']);
  }

  /**
   * Remove the updateLog tags from the solr config file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file object.
   */
  protected function processConfigFile(FileInterface $file) {
    $zip = new \ZipArchive();
    if ($zip->open($this->fileSystem->realpath($file->getFileUri())) == TRUE) {
      $oldSchemaFile = $zip->getFromName('solrconfig.xml');
      $newFile = preg_replace('/(\<updateLog\>.*\<\/updateLog\>)/s', '', $oldSchemaFile);
      $zip->deleteName('solrconfig.xml');
      $zip->addFromString('solrconfig.xml', $newFile);
      $zip->close();
    }
  }

  /**
   * Process the result and show the messages on the page.
   *
   * @param array $result
   *   The result from the POST request.
   * @param int $server
   *   The server id.
   */
  protected function processResult(array $result, $server) {
    if (!empty($result)) {
      if (empty($result['status'])) {
        $msg = is_array($result['msg']) ? implode('| ', $result['msg']) : $result['msg'];
        $this->loggerChannel->error($msg);
        $error = $this->t('An error occurred while trying to upload the configuration zip. Check the logs for more information.');
      }
      else {
        if (!empty($result['msg'])) {
          foreach ($result['msg'] as $file => $status) {
            if ($status == 'OK') {
              $processed[] = $file == 'Files upload' ? $this->zipFile->getFilename() : $file;
            }
            else {
              $errors[] = $file;
            }
          }
        }
        if (!empty($errors)) {
          // @todo Recheck this.
          try {
            $this->privateTempStore->get('search_api_opensolr')->set('invalid_files', $errors);
          }
          catch (TempStoreException $e) {
            Error::logException($this->loggerChannel, $e);
          }
          $error = $this->t('The following files were not successfully uploaded: @files. You can import these files again <a href="@link">here</a>.', [
            '@files' => implode(', ', $errors),
            '@link' => Url::fromRoute('search_api_opensolr.opensolr_config_files_import', ['search_api_server' => $server])->toString(),
          ]);
        }
        if (!empty($processed)) {
          $status = $this->t('The following files were uploaded successfully: @files.', [
            '@files' => implode(', ', $processed),
          ]);
        }
      }
    }
    else {
      $error = $this->t('An error occurred while trying to upload the configuration zip.');
    }

    // Show the message on the page.
    if (!empty($error)) {
      $this->messenger()->addError($error);
    }
    if (!empty($status)) {
      $this->messenger()->addStatus($status);
    }
  }

}
