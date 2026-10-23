<?php

namespace Drupal\search_api_opensolr_security\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Utility\Error;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndex;
use Drupal\search_api_opensolr_security\OpenSolrApi\Components\OpenSolrSecurity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for administrating the opensolr server security.
 *
 * @package Drupal\search_api_opensolr_security\Form
 */
class SecurityAdminForm extends FormBase implements SecurityAdminFormInterface {

  /**
   * The OpenSolr service that manages core security.
   *
   * @var \Drupal\search_api_opensolr_security\OpenSolrApi\Components\OpenSolrSecurity
   */
  protected $openSolrSecurity;

  /**
   * The OpenSolr service that manages indexes.
   *
   * @var \Drupal\search_api_opensolr\OpenSolrApi\Components\OpenSolrIndex
   */
  protected $openSolrIndex;

  /**
   * The id of the server's core (index).
   *
   * @var string
   */
  protected $coreName;

  /**
   * The search api server entity.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $searchApiServer;

  /**
   * Search API opensolr logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  public function __construct(OpenSolrSecurity $openSolrSecurity, OpenSolrIndex $openSolrIndex, LoggerChannelInterface $loggerChannel) {
    $this->openSolrSecurity = $openSolrSecurity;
    $this->openSolrIndex = $openSolrIndex;
    $this->loggerChannel = $loggerChannel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search_api_opensolr.client_security'),
      $container->get('search_api_opensolr.client_index'),
      $container->get('logger.channel.search_api_opensolr_security'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_opensolr_security_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?ServerInterface $search_api_server = NULL) {
    // @todo Maybe create a new backend to easily get the core id.
    $this->searchApiServer = $search_api_server;
    $backendConfig = $search_api_server->getBackendConfig();
    $this->coreName = $backendConfig['connector_config']['core'];
    $coreInfo = $this->openSolrIndex->getCoreInfo($this->coreName);
    $authEnabled = empty($coreInfo['info']['auth_username']) && empty($coreInfo['info']['auth_password']) ? FALSE : TRUE;

    $arguments = $authEnabled ? [
      '@color' => 'green',
      '@status' => 'enabled',
    ] : [
      '@color' => 'red',
      '@status' => 'disabled',
    ];
    $form['http_authentication'] = [
      '#type' => 'details',
      '#title' => $this->t('HTTP Authentication'),
      '#description' => $this->t('Authentication is currently <strong>@status</strong>', [
        '@status' => new FormattableMarkup('<span style="color: @color;">@status</span>', $arguments),
      ]),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    // @todo Currently there is no endpoint for retrieving existing values.
    $form['http_authentication']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Enter the http auth username.'),
      '#required' => TRUE,
    ];
    $form['http_authentication']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Enter the http auth password.'),
      '#required' => TRUE,
    ];
    $form['http_authentication']['actions'] = [
      '#type' => 'actions',
    ];
    $form['http_authentication']['actions']['add_http_auth'] = [
      '#type' => 'submit',
      '#value' => $authEnabled ? $this->t('Update HTTP auth') : $this->t('Add HTTP auth'),
      '#submit' => ['::addHttpAuth'],
      '#limit_validation_errors' => [['http_authentication']],
      '#name' => 'add-http',
    ];

    $form['ip_restrictions'] = [
      '#type' => 'details',
      '#title' => $this->t('IP restrictions'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['ip_restrictions']['ip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP address'),
      '#description' => $this->t('Enter the IP address you wish to set restriction for.'),
      '#required' => TRUE,
    ];
    $form['ip_restrictions']['handler'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Request handler'),
      '#description' => $this->t('Enter the the SOLR URI handler that the restriction will be applied to. E.g. /, /admin, /update etc.'),
      '#required' => TRUE,
    ];
    $form['ip_restrictions']['actions'] = [
      '#type' => 'actions',
    ];
    $form['ip_restrictions']['actions']['add_ip_restriction'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#submit' => ['::addIpRestriction'],
      '#limit_validation_errors' => [['ip_restrictions']],
    ];
    $this->attachExistingIps($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addHttpAuth(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('http_authentication');
    $response = $this->openSolrSecurity->updateHttpAuth($this->coreName, $values['username'], $values['password']);
    $server = $this->searchApiServer;
    if ($server instanceof ServerInterface) {
      $this->switchOpenSolrConnector($server, $form_state);
    }
    if ($response->isSuccess()) {
      $this->messenger()->addMessage($this->t('The HTTP Authentication has been successfully updated for the <em>@core</em> core.', ['@core' => $this->coreName]));
    }
    else {
      $this->messenger()->addError($this->t('Could not update the HTTP Authentication, try again later. Error message: <strong>@error_message</strong>', [
        '@error_message' => $response->getResponseData() ?? $response->getResponseData('error'),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addIpRestriction(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('ip_restrictions');
    $response = $this->openSolrSecurity->addIp($this->coreName, $values['ip'], $values['handler']);
    if ($response->isSuccess()) {
      $this->messenger()->addMessage($this->t('The <em>@ip</em> ip address was successfully added for the <em>@handler</em> handler.', [
        '@ip' => $values['ip'],
        '@handler' => $values['handler'],
      ]));
    }
    else {
      $this->messenger()->addError($this->t("The <em>@ip</em> ip address for the <em>@handler</em> handler couldn't be added. Check that the ip and the handler have correct values and try again.", [
        '@ip' => $values['ip'],
        '@handler' => $values['handler'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeIpRestrictions(array &$form, FormStateInterface $form_state) {
    $existingIpRestrictions = array_filter($form_state->getValue('existing_ip_restrictions'));
    $options = $form['existing_ip_restrictions']['current_restrictions']['#options'];
    $unsuccessful = [];
    foreach ($existingIpRestrictions['current_restrictions'] as $value) {
      $response = $this->openSolrSecurity->removeIp($this->coreName, $options[$value]['ip'], $options[$value]['handler']);
      if (!$response->isSuccess()) {
        $unsuccessful[] = $options[$value]['ip'];
      }
    }
    if (!empty($unsuccessful)) {
      $this->messenger()->addError($this->t("The <em>@ips</em> IPs couldn't be removed", [
        '@ips' => implode(', ', $unsuccessful),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('All IPs were successfully removed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Attaches the existing IP restrictions table to the given form element.
   *
   * @param array $element
   *   A full form array or a specific form element.
   */
  protected function attachExistingIps(array &$element) {
    $header = [
      'ip' => $this->t('IP Address'),
      'handler' => $this->t('Request handler'),
    ];
    $options = [];
    $ipsResponse = $this->openSolrSecurity->getIpList($this->coreName);
    if ($ipsResponse->isSuccess()) {
      $options = $ipsResponse->getResponseData();
    }
    else {
      $this->messenger()->addError($this->t('Could not retrieve the IPs list. Error message: <strong>@error_message</strong>', [
        '@error_message' => $ipsResponse->getResponseData() ?? $ipsResponse->getResponseData('error'),
      ]));
    }
    $element['existing_ip_restrictions'] = [
      '#type' => 'details',
      '#title' => $this->t('Current IP restrictions'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $element['existing_ip_restrictions']['current_restrictions'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t("There aren't any ip restrictions at the moment."),
    ];
    $element['existing_ip_restrictions']['actions'] = [
      '#type' => 'actions',
    ];
    $element['existing_ip_restrictions']['actions']['remove_ip_restrictions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove'),
      '#submit' => ['::removeIpRestrictions'],
      '#limit_validation_errors' => [['existing_ip_restrictions']],
    ];
  }

  /**
   * Switches between standard Opensolr and Basic Auth Opensolr connectors.
   *
   * When setting HTTP authentication, checks if the connector is the standard
   * one and changes it to the one that uses Basic Auth and vice versa when
   * removing the HTTP authentication. Also sets/removes the password and
   * username on the current server.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   The server entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function switchOpenSolrConnector(ServerInterface $server, FormStateInterface $form_state) {
    $backendConfig = $server->getBackendConfig();
    $values = $form_state->getValue('http_authentication');
    $triggeringElement = $form_state->getTriggeringElement();
    $triggeringElementName = !empty($triggeringElement['#name']) ? $triggeringElement['#name'] : '';
    if (!empty($backendConfig['connector'])) {
      // If the triggering element is add-http and the connector is opensolr
      // or basic auth opensolr, always set the connector to basic auth
      // opensolr and set/update the username and password.
      if ($triggeringElementName == 'add-http' && ($backendConfig['connector'] == 'opensolr' || $backendConfig['connector'] = 'basic_auth_opensolr')) {
        $backendConfig['connector'] = 'basic_auth_opensolr';
        $backendConfig['connector_config']['username'] = !empty($values['username']) ? $values['username'] : '';
        $backendConfig['connector_config']['password'] = !empty($values['password']) ? $values['password'] : '';
      }
      // Else if the triggering element is remove-http and the connector is
      // basic auth opensolr, remove the username and password from its config
      // and change back to the opensolr connector.
      elseif ($triggeringElementName == 'remove-http' && $backendConfig['connector'] == 'basic_auth_opensolr') {
        $backendConfig['connector'] = 'opensolr';
        unset($backendConfig['connector_config']['username']);
        unset($backendConfig['connector_config']['password']);
      }
      $server->setBackendConfig($backendConfig);
      try {
        $server->save();
      }
      catch (EntityStorageException $e) {
        Error::logException($this->loggerChannel, $e);
      }
    }
  }

}
