<?php

namespace Drupal\geolocation_here\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the HERE Maps form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class HereMapsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('geolocation_here.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HERE Maps API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('HERE Maps requires users to sign up at <a href="https://developer.here.com">developer.here.com</a>. The former App ID and App Code pair is no longer accepted by the HERE Maps API and has been replaced by a single API key.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'geolocation_here_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'geolocation_here.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory()->getEditable('geolocation_here.settings');
    $config->set('api_key', $form_state->getValue('api_key'));
    $config->save();

    // Confirmation on form submission.
    \Drupal::messenger()->addMessage($this->t('The configuration options have been saved.'));

    drupal_flush_all_caches();
  }

}
