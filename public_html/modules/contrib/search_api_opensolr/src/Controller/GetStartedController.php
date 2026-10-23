<?php

namespace Drupal\search_api_opensolr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\Services\OpenSolrConfig;
use Drupal\search_api_opensolr\Traits\OpensolrServerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get started page: the four steps from zero to a working opensolr index.
 *
 * Accounts can only be created on opensolr.com (the registration API this
 * page used to drive as a multistep form is gone), so this is a guide with a
 * live status per step, not a form.
 */
class GetStartedController extends ControllerBase {
  use OpensolrServerTrait;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\search_api_opensolr\Services\OpenSolrConfig $openSolrConfig
   *   The opensolr config service (saved credentials).
   */
  public function __construct(protected OpenSolrConfig $openSolrConfig) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('search_api_opensolr.config'));
  }

  /**
   * Builds the page.
   *
   * @return array
   *   A render array.
   */
  public function page(): array {
    $connected = $this->openSolrConfig->getEmail() !== '' && $this->openSolrConfig->getApiKey() !== '';
    [$serverCount, $indexCount] = $this->countOpensolrEntities();

    $steps = [
      [
        'title' => $this->t('Create an Opensolr account'),
        'description' => $this->t('Register at <a href=":url" target="_blank" rel="noopener">opensolr.com/register</a> — every new account starts with a 15-day free trial, no credit card required. Already have an account? Skip ahead.', [
          ':url' => 'https://opensolr.com/register',
        ]),
        'done' => $connected,
        'link' => Url::fromUri('https://opensolr.com/register'),
        'link_title' => $this->t('Register on opensolr.com'),
      ],
      [
        'title' => $this->t('Connect this site to your account'),
        'description' => $this->t('Copy your <strong>registration email</strong> and <strong>API key</strong> from your <a href=":url" target="_blank" rel="noopener">Opensolr dashboard</a> (Account Settings), paste them into <em>Settings</em> and click <em>Test connection</em>.', [
          ':url' => 'https://opensolr.com/admin/solr_manager/dashboard',
        ]),
        'done' => $connected,
        'link' => Url::fromRoute('search_api_opensolr.opensolr_config_form'),
        'link_title' => $this->t('Open Settings'),
      ],
      [
        'title' => $this->t('Create your Opensolr index and Search API server'),
        'description' => $this->t('On the <em>Autoconfigure</em> tab pick a region: the module creates the Solr index in your Opensolr account, uploads the Search API Solr configuration to it and creates the matching Search API server for you.'),
        'done' => $serverCount > 0,
        'link' => Url::fromRoute('search_api_opensolr.autoconfigure_form'),
        'link_title' => $this->t('Open Autoconfigure'),
      ],
      [
        'title' => $this->t('Create your Search API index'),
        'description' => $this->t('From here on it is plain Search API: add an index on the new server, choose the content and fields to index, then index your content and build a search page or view — exactly as you would with any Solr server.'),
        'done' => $indexCount > 0,
        'link' => Url::fromRoute('search_api.overview'),
        'link_title' => $this->t('Go to Search API'),
      ],
    ];

    // The status per step depends on the saved credentials and on the Search
    // API servers/indexes, so the page has to be invalidated with them.
    return [
      '#theme' => 'opensolr_get_started',
      '#steps' => $steps,
      '#attached' => ['library' => ['search_api_opensolr/get_started']],
      '#cache' => [
        'tags' => ['config:search_api_opensolr.opensolrconfig', 'search_api_server_list', 'search_api_index_list'],
      ],
    ];
  }

  /**
   * Counts the Search API servers on an opensolr connector and their indexes.
   *
   * @return int[]
   *   [server count, index count].
   */
  protected function countOpensolrEntities(): array {
    $servers = 0;
    $indexes = 0;
    /** @var \Drupal\search_api\ServerInterface $server */
    foreach ($this->entityTypeManager()->getStorage('search_api_server')->loadMultiple() as $server) {
      if ($server instanceof ServerInterface && $this->isOpenSolrServer($server)) {
        $servers++;
        $indexes += count($server->getIndexes());
      }
    }
    return [$servers, $indexes];
  }

}
