<?php

namespace Drupal\search_api_opensolr\Plugin\SolrConnector;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_opensolr\Traits\OpensolrKeyDependencyTrait;
use Drupal\search_api_solr\SolrConnector\BasicAuthTrait;

/**
 * OpenSolr connector.
 *
 * @SolrConnector(
 *   id = "basic_auth_opensolr",
 *   label = @Translation("Opensolr with Basic Auth (recommended)"),
 *   description = @Translation("A connector usable for Solr installations managed through opensolr that uses basic auth.")
 * )
 */
class BasicAuthOpensolrSolrConnector extends OpensolrSolrConnector {

  use OpensolrKeyDependencyTrait;
  use BasicAuthTrait {
    buildConfigurationForm as traitBuildConfigurationForm;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->traitBuildConfigurationForm($form, $form_state);
    $form['auth']['#description'] = $this->t('Your <strong>opensolr</strong> index security information is <strong>prefilled automatically</strong>. If you changed it in opensolr, simply re-save this form. You can also use Search API opensolr seucurity module to manage authentication security.');
    $credentials = $this->openSolrConfig->getApiCredentials();
    $form['auth']['username']['#default_value'] = $credentials['email'];
    $form['auth']['username']['#access'] = FALSE;
    $form['auth']['password']['#default_value'] = $credentials['api_key'] ?? $credentials['api_key_raw'];
    $form['auth']['password']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    // Override the default values if the index values are different.
    $coreName = $form_state->getValue(['opensolr', 'index']);
    if (empty($coreName)) {
      $form_state->setErrorByName('opensolr', $this->t('No valid index was selected'));
    }
    else {
      $core = $this->openSolrIndex->getCoreInfo($coreName);
      $username = NestedArray::getValue($core, ['info', 'auth_username']);
      $password = NestedArray::getValue($core, ['info', 'auth_password']);
      if (empty($username) && empty($password)) {
        $form_state->setErrorByName('opensolr', $this->t('Failed to retrieve the core auth information.'));
      }
      else {
        $form_state->setValue('auth', [
          'username' => $username,
          'password' => $password,
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $settings = parent::viewSettings();
    $coreName = $this->getOpensolrCoreName();
    if (!empty($coreName)) {
      $coreData = $this->openSolrIndex->getCoreInfo($coreName)['core_data'] ?? [];
      if (isset($coreData['core_size_mb']) && isset($coreData['max_core_size_mb'])) {
        $settings[] = $this->getViewSettingsInfo($coreData['core_size_mb'], $coreData['max_core_size_mb'], $this->t('Index Consumed Disk'));
      }
      if (isset($coreData['core_badnwidth_mb']) && isset($coreData['max_badnwdith_mb'])) {
        $settings[] = $this->getViewSettingsInfo($coreData['core_badnwidth_mb'], $coreData['max_badnwdith_mb'], $this->t('Index Consumed Monthly Traffic Bandwidth'));
      }
    }
    return $settings;
  }

  /**
   * Creates the settings info for the given core info.
   *
   * @param float $used
   *   The amount in Mb used.
   * @param float $max
   *   The maximum amount allowed in Mb.
   * @param string $label
   *   The label of the view setting information.
   *
   * @return array
   *   Returns the info array with the label, message and status.
   */
  protected function getViewSettingsInfo(float $used, float $max, string $label) {
    $status = 'ok';
    $info = $this->t("%used Mb / %max Mb Max Allowed", [
      '%used' => $used,
      '%max' => $max,
    ]);
    if ($used >= $max) {
      $info = $this->t('%used Mb / %max Mb Max Allowed <a href=":url" target="_blank">Upgrade</a>', [
        '%used' => $used,
        '%max' => $max,
        ':url' => 'https://opensolr.com/pricing',
      ]);
      $status = 'error';
    }
    return [
      'label' => $label,
      'info' => $info,
      'status' => $status,
    ];
  }

}
