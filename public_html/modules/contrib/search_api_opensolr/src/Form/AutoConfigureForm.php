<?php

namespace Drupal\search_api_opensolr\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api_opensolr\AutoConfig;
use Drupal\search_api_opensolr\AutoConfigBatch;
use Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndexInterface;
use Drupal\search_api_opensolr\OpenSolrApi\OpenSolrException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Opensolr autoconfigure form builder.
 */
class AutoConfigureForm extends FormBase {

  /**
   * The opensolr index client.
   *
   * @var \Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndexInterface
   */
  protected OpenSolrIndexInterface $indexClient;

  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected ClassResolverInterface $classResolver;

  public function __construct(OpenSolrIndexInterface $openSolrIndex, ClassResolverInterface $classResolver) {
    $this->indexClient = $openSolrIndex;
    $this->classResolver = $classResolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search_api_opensolr.client_index'),
      $container->get('class_resolver'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opensolr_autoconfigure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help_top'] = [
      '#theme' => 'opensolr_help_autoconfigure',
    ];
    $response = $this->indexClient->getEnvironments();
    if ($response->isSuccess()) {
      $data = $response->getResponseData(['msg', 'simple_shared_environments']);
      $form['environment'] = $this->buildEnvironmentsTable($data);

      $form['help_bottom'] = [
        '#type' => 'inline_template',
        '#template' => '<p>{{ info_note }} <a href="https://opensolr.com/contact?subject=server_request" target="_blank">{{ contact_title }}</a></p>',
        '#context' => [
          'info_note' => $this->t("Didn't find a suitable region/version?"),
          'contact_title' => $this->t('Contact support'),
        ],
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Start'),
        '#button_type' => 'primary',
      ];
    }
    else {
      $this->messenger()->addError($this->t('Could not connect to opensolr. Either the services are down, or you have not configured the <a href=":url">opensolr settings</a> yet.', [
        ':url' => Url::fromRoute('search_api_opensolr.opensolr_config_form')->toString(),
      ]));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $environment = $form_state->getValue('environment');
    /** @var \Drupal\search_api_opensolr\AutoConfig $autoconfig */
    $autoconfig = $this->classResolver->getInstanceFromDefinition(AutoConfig::class);
    try {
      $autoconfig->init($environment);
      $batchBuilder = (new BatchBuilder())
        ->setTitle($this->t('Creating your opensolr setup, hang on'))
        ->setFinishCallback([AutoConfigBatch::class, 'finish'])
        ->setInitMessage($this->t('Initializing the configuration'));
      $steps = [
        AutoConfig::STEP_CREATE_CORE,
        AutoConfig::STEP_CREATE_SERVER,
      ];
      foreach ($steps as $step) {
        $batchBuilder->addOperation([AutoConfigBatch::class, 'process'], [$autoconfig, $step]);
      }
      batch_set($batchBuilder->toArray());
    }
    catch (OpenSolrException $e) {
      $this->messenger()->addError($e->getMessage());
    }
  }

  /**
   * Builds a table select with the available opensolr environments.
   *
   * @param array $data
   *   An array containing the environments retrieved from opensolr.
   *
   * @return array
   *   Returns an array with the form element.
   */
  protected function buildEnvironmentsTable(array $data) {
    $header = [
      'server_identifier' => $this->t('Identifier'),
      'solr_version' => $this->t('Solr version'),
      'region' => $this->t('Region'),
      'provider' => $this->t('Provider'),
      'country' => $this->t('Country'),
    ];
    $options = [];
    $recommended = array_filter($data, function ($environment) {
      $version = (float) $environment['solr_version'];
      return $version >= 8;
    });
    foreach ($recommended as $environment) {
      $options[$environment['server_identifier']] = $environment;
    }
    return [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#empty' => $this->t('No environments found'),
    ];
  }

}
