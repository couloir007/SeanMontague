<?php

namespace Drupal\ultimate_cron\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for general cron settings.
 */
class GeneralSettingsForm extends ConfigFormBase {

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->state = $container->get('state');
    $form->dateFormatter = $container->get('date.formatter');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ultimate_cron_general_settings';
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

    // @todo enable this when supported again
    $form['nodejs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('nodejs'),
      '#default_value' => $config->get('nodejs'),
      '#description' => $this->t('Enable nodejs integration (Live reload on jobs page. Requires the nodejs module to be installed and configured).'),
      '#fallback' => TRUE,
      '#access' => FALSE,
    ];

    // Queue settings. Visual hierarchy disabled since this is currently
    // the only general settings group.
    $form['queue'] = [
      //'#type' => 'details',
      //'#title' => 'queue',
      //'#group' => 'settings_tabs',
      '#tree' => TRUE,
    ];

    $form['queue']['enabled'] = [
      '#title' => $this->t('Override cron queue processing'),
      '#description' => $this->t('If enabled, queue workers are exposed as cron jobs and can be configured separately. When disabled, the standard queue processing is used. <strong>This feature is currently experimental, do not enable unless you need it.</strong>'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('queue.enabled'),
      '#fallback' => TRUE,
    ];

    $queue_states = [
      '#states' => [
        'visible' => [':input[name="queue[enabled]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['queue']['timeouts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Timeouts'),
    ] + $queue_states;
    $form['queue']['timeouts']['lease_time'] = [
      '#title' => $this->t("Queue lease time"),
      '#type' => 'number',
      '#default_value' => $config->get('queue.timeouts.lease_time'),
      '#description' => $this->t('Seconds to claim a cron queue item.'),
      '#fallback' => TRUE,
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.01,
    ];
    $form['queue']['timeouts']['time'] = [
      '#title' => $this->t('Time'),
      '#type' => 'number',
      '#default_value' => $config->get('queue.timeouts.time'),
      '#description' => $this->t('Time in seconds to process items during a cron run.'),
      '#fallback' => TRUE,
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.01,
    ];

    $form['queue']['delays'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Delays'),
    ] + $queue_states;
    $form['queue']['delays']['empty_delay'] = [
      '#title' => $this->t("Empty delay"),
      '#type' => 'number',
      '#default_value' => $config->get('queue.delays.empty_delay'),
      '#description' => $this->t('Seconds to delay processing of queue if queue is empty (0 = end job).'),
      '#fallback' => TRUE,
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.01,
    ];
    $form['queue']['delays']['item_delay'] = [
      '#title' => $this->t("Item delay"),
      '#type' => 'number',
      '#default_value' => $config->get('queue.delays.item_delay'),
      '#description' => $this->t('Seconds to wait between processing each item in a queue.'),
      '#fallback' => TRUE,
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.01,
    ];

    $throttle_states = [
      '#states' => [
        'visible' => [':input[name="queue[throttle][enabled]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['queue']['throttle'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Throttling'),
      // @todo Show when throttling is implemented.
      '#access' => FALSE,
    ] + $queue_states;
    $form['queue']['throttle']['enabled'] = [
      '#title' => $this->t('Throttle'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('queue.throttle.enabled'),
      '#description' => $this->t('Throttle queues using multiple threads.'),
    ];
    $form['queue']['throttle']['threads'] = [
      '#title' => $this->t('Threads'),
      '#type' => 'number',
      '#default_value' => $config->get('queue.throttle.threads'),
      '#description' => $this->t('Number of threads to use for queues.'),
      '#fallback' => TRUE,
      '#required' => TRUE,
      '#min' => 0,
    ] + $throttle_states;
    $form['queue']['throttle']['threshold'] = [
      '#title' => $this->t('Threshold'),
      '#type' => 'number',
      '#default_value' => $config->get('queue.throttle.threshold'),
      '#description' => $this->t('Number of items in queue required to activate the next cron job.'),
      '#fallback' => TRUE,
      '#required' => TRUE,
      '#min' => 0,
    ] + $throttle_states;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ultimate_cron.settings')
      ->set('queue', $form_state->getValue('queue'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
