<?php

namespace Drupal\ultimate_cron\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for logger settings.
 */
class LoggerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ultimate_cron_logger_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ultimate_cron.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ultimate_cron.settings');

    // Setup vertical tabs.
    $form['settings_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    // Settings for Cache logger.
    $form['cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache'),
      '#group' => 'settings_tabs',
      '#tree' => TRUE,
    ];

    $form['cache']['bin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache bin'),
      '#description' => $this->t('Select which cache bin to use for storing logs.'),
      '#default_value' => $config->get('logger.cache.bin'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    ];
    $form['cache']['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache timeout'),
      '#description' => $this->t('Seconds before cache entry expires (0 = never, -1 = on next general cache wipe).'),
      '#default_value' => $config->get('logger.cache.timeout'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    ];

    // Settings for Database logger.
    $form['database'] = [
      '#type' => 'details',
      '#title' => $this->t('Database'),
      '#group' => 'settings_tabs',
      '#tree' => TRUE,
    ];
    $options['method'] = [
      1 => $this->t('Disabled'),
      2 => $this->t('Remove logs older than a specified age'),
      3 => $this->t('Retain only a specific amount of log entries'),
    ];
    $form['database']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Log entry cleanup method'),
      '#description' => $this->t('Select which method to use for cleaning up logs.'),
      '#options' => $options['method'],
      '#default_value' => $config->get('logger.database.method'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    ];

    $states = ['expire' => [], 'retain' => []];
    $form['database']['method_expire'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Remove logs older than a specified age'),
    ] + $states['expire'];
    $form['database']['method_expire']['expire'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log entry expiration'),
      '#description' => $this->t('Remove log entries older than X seconds.'),
      '#default_value' => $config->get('logger.database.method_expire.expire'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    ] + $states['expire'];

    $form['database']['method_retain'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Retain only a specific amount of log entries'),
    ] + $states['retain'];
    $form['database']['method_retain']['retain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Retain logs'),
      '#description' => $this->t('Retain X amount of log entries.'),
      '#default_value' => $config->get('logger.database.method_retain.retain'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    ] + $states['retain'];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ultimate_cron.settings')
      ->set('logger.cache', $form_state->getValue('cache'))
      ->set('logger.database', $form_state->getValue('database'))
      ->save('');

    parent::submitForm($form, $form_state);
  }

}
