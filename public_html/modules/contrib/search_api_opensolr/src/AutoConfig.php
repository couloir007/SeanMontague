<?php

namespace Drupal\search_api_opensolr;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\search_api\Backend\BackendPluginManager;
use Drupal\search_api\Entity\Server;
use Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndexInterface;
use Drupal\search_api_opensolr\OpenSolrApi\OpenSolrException;
use Drupal\search_api_opensolr\Services\OpenSolrConfig;
use Drupal\search_api_opensolr\Traits\OpenSolrConnectorTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Autoconfigures opensolr index & search api server creation.
 */
class AutoConfig implements ContainerInjectionInterface {
  use DependencySerializationTrait;
  use OpenSolrConnectorTrait;
  use StringTranslationTrait;

  const STEP_CREATE_CORE = 1;
  const STEP_CREATE_SERVER = 2;

  /**
   * The opensolr index client.
   *
   * @var \Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndexInterface
   */
  protected OpenSolrIndexInterface $indexClient;

  /**
   * The opensolr config service.
   *
   * @var \Drupal\search_api_opensolr\Services\OpenSolrConfig
   */
  protected OpenSolrConfig $openSolrConfig;

  /**
   * The Search Api backend plugin manager.
   *
   * @var \Drupal\search_api\Backend\BackendPluginManager
   */
  protected BackendPluginManager $backendPluginManager;

  /**
   * The opensolr environment ID.
   *
   * @var string
   */
  protected string $environment;

  /**
   * The core name to create in opensolr.
   *
   * @var string
   */
  protected string $coreName;

  /**
   * The autoconfigure process errors.
   *
   * @var array
   */
  protected array $errors = [];

  public function __construct(OpenSolrIndexInterface $openSolrIndex, OpenSolrConfig $openSolrConfig, BackendPluginManager $backendPluginManager) {
    $this->indexClient = $openSolrIndex;
    $this->openSolrConfig = $openSolrConfig;
    $this->backendPluginManager = $backendPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search_api_opensolr.client_index'),
      $container->get('search_api_opensolr.config'),
      $container->get('plugin.manager.search_api.backend'),
    );
  }

  /**
   * Inits the required data for the autoconfigure batch process.
   *
   * @param string $environment
   *   The opensolr environment ID.
   *
   * @throws \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrException
   */
  public function init(string $environment = '') {
    if (empty($environment)) {
      throw new OpenSolrException(NULL, 'Cannot proceed without an environment ID.');
    }
    $email = $this->openSolrConfig->getEmail();
    if (empty($email)) {
      throw new OpenSolrException(NULL, 'Cannot proceed without an opensolr email.');
    }
    $this->environment = $environment;
    $this->coreName = md5($email . mt_rand() . time() . microtime() . uniqid());
  }

  /**
   * Creates an opensolr index.
   *
   * @param int $step
   *   The current batch step.
   * @param array $context
   *   The batch context.
   */
  public function createOpensolrIndex(int $step, array &$context) {
    $context['message'] = $this->t('Creating the index in your opensolr account');
    $response = $this->indexClient->createCore($this->coreName, $this->environment);
    if (!$response->isSuccess()) {
      $context['results']['errors'][] = OpenSolrErrors::getError($response->getResponseData());
    }
    else {
      $context['results']['success'][] = $this->t('Your <a href="https://opensolr.com/admin/solr_manager/tools/@core_name#overview" target="_blank">opensolr index</a> was successfully created.', [
        '@core_name' => $this->coreName,
      ]);
    }
  }

  /**
   * Creates a Search API server.
   *
   * @param int $step
   *   The current batch step.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\search_api\SearchApiException|\Drupal\Core\Entity\EntityMalformedException
   */
  public function createSearchApiServer(int $step, array &$context) {
    $context['message'] = $this->t('Creating the Drupal opensolr server');
    $index = $this->indexClient->getCoreInfo($this->coreName)['info'] ?? [];
    if (empty($index)) {
      $context['results']['errors'][] = $this->t('Could not retrieve the index data for the %index core. Either it does not exist or the opensolr services are unavailable.', [
        '%index' => $this->coreName,
      ]);
      return;
    }

    /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend $backend */
    $backend = $this->backendPluginManager->createInstance('search_api_solr', [
      'connector' => 'basic_auth_opensolr',
      'connector_config' => [
        'opensolr' => [
          'index' => $this->coreName,
          'index_type' => $this->getConnectorValueByKey('index_type', $index),
        ],
      ],
    ]);
    $backendConfig = $backend->getConfiguration();
    $connector_config = $backend->getSolrConnector()->getConfiguration();
    foreach ($this->getOpenSolrConfigs() as $configName) {
      $connector_config[$configName] = $this->getConnectorValueByKey($configName, $index);
    }
    $backendConfig['connector_config'] = $connector_config;
    $server = Server::create([
      'id' => uniqid('opensolr_'),
      'name' => 'opensolr server',
      'description' => sprintf("Server generated automatically for the %s opensolr index.", $this->coreName),
      'status' => 1,
      'backend' => 'search_api_solr',
    ]);
    $server->setBackendConfig($backendConfig);
    try {
      $server->save();
      $context['results']['success'][] = $this->t('Your <a href=":server_url">Drupal server</a> was successfully created. You can now start by <a href=":index_url">adding an index</a>.', [
        ':server_url' => $server->toUrl()->toString(),
        ':index_url' => Url::fromRoute('entity.search_api_index.add_form')->toString(),
      ]);
    }
    catch (EntityStorageException $e) {
      $context['results']['errors'][] = $this->t('Failed to create the Search API server.');
    }
  }

  /**
   * Triggers the given batch step process.
   *
   * @param int $step
   *   The current batch step.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\search_api\SearchApiException|\Drupal\Core\Entity\EntityMalformedException
   */
  public function doAutoCreate(int $step, array &$context) {
    switch ($step) {
      case AutoConfig::STEP_CREATE_CORE:
        $this->createOpensolrIndex($step, $context);
        break;

      case AutoConfig::STEP_CREATE_SERVER:
        if (empty($context['results']['errors'])) {
          // There is no point in continuing with this step if there are errors.
          $this->createSearchApiServer($step, $context);
        }
        break;
    }
  }

}
