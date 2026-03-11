<?php

declare(strict_types=1);

namespace Drupal\local_tasks_more\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\local_tasks_more\LocalTasksMoreManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Local Tasks More settings for this site.
 *
 * This config form is using the new '#config_target' property
 * to trigger validation constraints.
 *
 * @see https://www.drupal.org/node/3373502
 */
class LocalTasksMoreSettingsForm extends ConfigFormBase {

  /**
   * The cache tag invalidator service.
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * The local tasks more manager.
   */
  protected LocalTasksMoreManagerInterface $localTasksMoreManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->cacheTagsInvalidator = $container->get('cache_tags.invalidator');
    $instance->localTasksMoreManager = $container->get('local_tasks_more.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'local_tasks_more_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This form uses #config_target instead.
    return ['local_tasks_more.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Base routes.
    $form['base_routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Base routes'),
      '#description' => $this->t('Enter the base routes, that support the show more/less task link and alterations.'),
      '#config_target' => new ConfigTarget(
        'local_tasks_more.settings',
        'base_routes',
        // Converts config value to a form value.
        fn($value) => implode("\n", $value),
        // Converts form value to a config value.
        fn($value) => array_unique(array_map('trim', explode("\n", trim($value)))),
      ),
    ];
    $base_routes = $this->localTasksMoreManager->getBaseRoutes();
    $base_routes = array_unique($base_routes);
    asort($base_routes);
    $form['base_routes_example'] = [
      '#type' => 'details',
      '#title' => $this->t('Base route information'),
      '#description' => $this->t('Below are all the base routes that have local tasks.'),
      'examples' => [
        '#markup' => implode('<br/>', $base_routes),
      ],
    ];

    // Local tasks alterations.
    $form['alter_local_tasks'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Local tasks alterations (YAML)'),
      '#description' => $this->t('Enter the local task id and this the altered title and weight. Set the local tasks to FALSE to remove it.'),
      '#element_validate' => [[static::class, 'alterLocalTasksElementValidate']],
      '#config_target' => new ConfigTarget(
        'local_tasks_more.settings',
        'alter_local_tasks',
        [static::class, 'alterLocalTasksFromConfig'],
        [static::class, 'alterLocalTasksToConfig'],
      ),
    ];
    $form['alter_local_tasks_examples'] = [
      '#type' => 'details',
      '#title' => $this->t('Local task information'),
      '#description' => $this->t('Below is the unaltered local task information for the selected base routes.'),
      'examples' => [
        '#markup' => $this->getLocalTasksExamplesAsYaml(),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];

    // Level (primary and secondary) limits and thresholds.
    $levels = [
      'primary' => $this->t('Primary'),
      'secondary' => $this->t('Secondary'),
    ];
    foreach ($levels as $level => $label) {
      $t_args = ['@label' => $label];

      $form[$level] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@label local tasks settings', $t_args),
        '#tree' => TRUE,
      ];
      $form[$level]['limit'] = [
        '#type' => 'number',
        '#title' => $this->t('@label local tasks limit', $t_args),
        '#description' => $this->t('Enter the number of links visible before the show more/less tasks link.'),
        '#required' => TRUE,
        '#min' => 2,
        '#max' => 12,
        '#config_target' => "local_tasks_more.settings:$level.limit",
      ];
      $form[$level]['threshold'] = [
        '#type' => 'number',
        '#title' => $this->t('@label local tasks threshold', $t_args),
        '#description' => $this->t('Enter the number of links past the limit to trigger the display of the show more/less tasks link.'),
        '#required' => TRUE,
        '#min' => 2,
        '#max' => 12,
        '#config_target' => "local_tasks_more.settings:$level.threshold",
      ];
    }

    $form['#attached']['library'][] = 'local_tasks_more/local_tasks_more.admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $this->cacheTagsInvalidator->invalidateTags(['local_task']);
  }

  /**
   * Retrieves local tasks as YAML string.
   *
   * @return string
   *   The YAML representation of the local tasks.
   */
  protected function getLocalTasksExamplesAsYaml(): string {
    $keys = [
      'title' => 'title',
      'weight' => 'weight',
    ];

    $base_routes = $this->config('local_tasks_more.settings')
      ->get('base_routes');
    $local_tasks = array_intersect_key(
      $this->localTasksMoreManager->getLocalTasks(), array_combine($base_routes, $base_routes)
    );

    $lines = [];
    foreach ($local_tasks as $base_route => $primary_tasks) {
      $lines[] = '# ' . $base_route;
      foreach ($primary_tasks as $primary_plugin_id => $primary_task_info) {
        $primary_task_info['title'] = (string) $primary_task_info['title'];

        $lines[] = trim(Yaml::encode([$primary_plugin_id => array_intersect_key($primary_task_info, $keys)]));
        foreach ($primary_task_info['children'] as $secondary_plugin_id => $secondary_task_info) {
          $secondary_task_info['title'] = (string) $secondary_task_info['title'];

          $lines[] = trim(Yaml::encode([$secondary_plugin_id => array_intersect_key($secondary_task_info, $keys)]));
        }
      }
      $lines[] = '';
    }
    return implode(PHP_EOL, $lines);
  }

  /* ************************************************************************ */
  // Alter local tasks callbacks.
  /* ************************************************************************ */

  /**
   * Validates an element containing YAML data.
   *
   * @param array $element
   *   The form element that contains the YAML data to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $completed_form
   *   The completed form array.
   */
  public static function alterLocalTasksElementValidate(array &$element, FormStateInterface $form_state, array &$completed_form): void {
    try {
      Yaml::decode($element['#value']);
    }
    catch (\Exception $exception) {
      $t_args = [
        '@name' => $element['#title'],
        '%error' => $exception->getMessage(),
      ];
      $form_state->setError($element, t('@name field is not valid YAML. %error', $t_args));
    }
  }

  /**
   * Converts an array of items into a YAML string based on configuration.
   *
   * @param array $items
   *   An array of items to be converted into YAML format.
   *
   * @return string
   *   The YAML representation of the items based on the given configuration.
   */
  public static function alterLocalTasksFromConfig(array $items): string {
    $data = [];
    foreach ($items as $item) {
      $plugin_id = $item['plugin_id'];
      if (isset($item['status']) && $item['status'] === FALSE) {
        $data[$plugin_id] = FALSE;
      }
      else {
        unset($item['plugin_id']);
        $data[$plugin_id] = $item;
      }
    }
    return Yaml::encode($data);
  }

  /**
   * Converts a YAML string to an array configuration.
   *
   * @param string $value
   *   The YAML string to decode and convert.
   *
   * @return array|null
   *   An array configuration with plugin IDs and their corresponding data.
   */
  public static function alterLocalTasksToConfig(string $value): ?array {
    try {
      $data = Yaml::decode($value);
      $items = [];
      foreach ($data as $plugin_id => $item) {
        if ($item === FALSE) {
          $items[] = ['plugin_id' => $plugin_id, 'status' => FALSE];
        }
        else {
          $items[] = ['plugin_id' => $plugin_id] + $item;
        }
      }
      return $items;
    }
    catch (\Exception $exception) {
      return NULL;
    }
  }

}
