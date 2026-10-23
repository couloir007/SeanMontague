<?php

namespace Drupal\search_api_opensolr\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndex;
use Drupal\search_api_opensolr\OpenSolrApi\OpenSolrException;
use Drupal\search_api_opensolr\OpenSolrErrors;
use Drupal\search_api_opensolr\Services\OpenSolrConfig;
use Drupal\search_api_opensolr\Traits\OpensolrKeyDependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the opensolr configuration form.
 *
 * @package Drupal\search_api_opensolr\Form
 */
class OpenSolrConfigForm extends ConfigFormBase {
  use OpensolrKeyDependencyTrait;

  /**
   * OpenSolr Index api component.
   *
   * @var \Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndex
   */
  protected $searchApiOpensolrClient;


  /**
   * The opensolr config service.
   *
   * @var \Drupal\search_api_opensolr\Services\OpenSolrConfig
   */
  protected OpenSolrConfig $openSolrConfig;

  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, OpenSolrIndex $search_api_opensolr_index, OpenSolrConfig $openSolrConfig) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->searchApiOpensolrClient = $search_api_opensolr_index;
    $this->openSolrConfig = $openSolrConfig;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('search_api_opensolr.client_index'),
      $container->get('search_api_opensolr.config'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['search_api_opensolr.opensolrconfig'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opensolr_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $opensolrConfig = $this->config('search_api_opensolr.opensolrconfig');
    $config = $opensolrConfig->get('opensolr_credentials');
    $keyAvailable = $this->keyModuleAvailable();
    // The Key integration is an explicit opt-in (issue #3576419): installing
    // Key for another purpose must not silently change how we store the key.
    $useKey = $keyAvailable && (bool) $opensolrConfig->get('use_key_module');

    $form['use_key_module'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Store the API key with the Key module'),
      '#default_value' => $useKey,
      '#disabled' => !$keyAvailable,
      '#description' => $keyAvailable
        ? $this->t('Keep the opensolr API key in a <a href="https://www.drupal.org/project/key" target="_blank">Key</a> entity (authentication type) instead of the module configuration.')
        : $this->t('Requires the <a href="https://www.drupal.org/project/key" target="_blank">Key module</a>. Install it to enable this option; until then the API key is stored in the module configuration.'),
    ];

    $form['opensolr_credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('Opensolr API Credentials'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $form['opensolr_credentials']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Your opensolr email.'),
      '#default_value' => $config['email'] ?? '',
      '#required' => TRUE,
    ];
    // Direct storage field. Always rendered; hidden via #states while the Key
    // integration is selected. Not #required — a hidden required field would
    // block the submit — the check happens in validateForm() instead.
    $form['opensolr_credentials']['api_key_raw'] = [
      '#type' => 'password',
      '#title' => $this->t('Secret key'),
      '#attributes' => [
        'class' => ['pass-show'],
      ],
      '#prefix' => '<div class="pass-show-wrapper">',
      '#suffix' => '</div>',
      '#description' => $this->t('The secret key can be retrieved from your opensolr account.'),
      '#default_value' => $config['api_key_raw'] ?? '',
    ];
    if ($keyAvailable) {
      $form['opensolr_credentials']['api_key_raw']['#states'] = [
        'visible' => [':input[name="use_key_module"]' => ['checked' => FALSE]],
      ];
      $form['opensolr_credentials']['api_key'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Secret key'),
        '#description' => $this->t('The key has to belong to the <strong>authentication</strong> group.'),
        '#key_filters' => ['type_group' => 'authentication'],
        '#default_value' => $config['api_key'] ?? '',
        '#empty_option' => $this->t('- Select -'),
        '#states' => [
          'visible' => [':input[name="use_key_module"]' => ['checked' => TRUE]],
        ],
      ];
    }
    else {
      $form['opensolr_credentials']['api_key'] = [
        '#type' => 'hidden',
        '#value' => $config['api_key'] ?? '',
      ];
    }
    // The password element never renders its #default_value; the JS fills it
    // in from drupalSettings and adds the show/hide toggle.
    $form['#attached']['library'][] = 'search_api_opensolr/admin-tweaks';
    $form['#attached']['drupalSettings']['opensolr_key'] = $config['api_key_raw'] ?? '';

    $form = parent::buildForm($form, $form_state);
    $form['actions']['connection'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test connection'),
      '#submit' => ['::testConnectionSubmit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Exactly one of the two key fields is visible, depending on the checkbox;
    // require the visible one.
    $useKey = $this->keyModuleAvailable() && $form_state->getValue('use_key_module');
    if ($useKey && $form_state->getValue(['opensolr_credentials', 'api_key']) === '') {
      $form_state->setErrorByName('opensolr_credentials][api_key', $this->t('Select the key that holds your opensolr secret key.'));
    }
    if (!$useKey && $form_state->getValue(['opensolr_credentials', 'api_key_raw']) === '') {
      $form_state->setErrorByName('opensolr_credentials][api_key_raw', $this->t('The secret key is required.'));
    }
  }

  /**
   * Submission handler to test the opensolr connection.
   */
  public function testConnectionSubmit(array &$form, FormStateInterface $form_state) {
    $credentials = $form_state->getValue('opensolr_credentials');
    // Resolve the key the way the form is currently set, not the way it was
    // last saved — the user may be testing before saving the toggle.
    $useKey = $this->keyModuleAvailable() && (bool) $form_state->getValue('use_key_module');
    $credentials = $this->openSolrConfig->extractApiCredentials($credentials, $useKey);
    // Make a test call to the API to make sure that the credentials are
    // correct. We keep this here for now because of fallback to previous
    // development.
    try {
      $result = $this->searchApiOpensolrClient->getIndexList($credentials);

      if (isset($result['status']) && $result['status'] === FALSE) {
        $this->messenger()->addError($this->t('The connection to OpenSolr services could not be established. Check your credentials.'));
      }
      else {
        // If we reached this point, it means that the test connection was
        // successful.
        $this->messenger()->addStatus($this->t('The test connection to OpenSolr services was successful.'));
      }
    }
    catch (OpenSolrException $exception) {
      $this->messenger()->addError($this->t('The opensolr test connection failed with the message <em>@message</em>. Make sure that you have <strong>at least one index</strong> available before testing the connection.<br> You can create one in your opensolr account or by using the <a href=":url">autoconfigure feature</a>.', [
        '@message' => OpenSolrErrors::getError($exception->getMessage()),
        ':url' => Url::fromRoute('search_api_opensolr.autoconfigure_form')->toString(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Save the configuration.
    $this->config('search_api_opensolr.opensolrconfig')
      ->set('use_key_module', $this->keyModuleAvailable() && (bool) $form_state->getValue('use_key_module'))
      ->set('opensolr_credentials', $form_state->getValue('opensolr_credentials'))
      ->save();
  }

}
