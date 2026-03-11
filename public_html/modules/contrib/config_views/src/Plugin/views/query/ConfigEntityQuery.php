<?php

namespace Drupal\config_views\Plugin\views\query;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\views\query\DateSqlInterface;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This query is able to work with config entities.
 *
 * @ViewsQuery(
 *   id = "views_config_entity_query",
 *   title = @Translation("Entity Query"),
 *   help = @Translation("Query will be generated and run using the Drupal Entity Query API.")
 * )
 */
class ConfigEntityQuery extends Sql {

  /**
   * Storage for commands to be executed on applied conditions.
   *
   * @var array
   */
  protected array $commands = [];

  /**
   * Storage for conditions to be applied.
   *
   * @var array
   */
  protected array $entityConditionGroups;

  /**
   * Storage for the sorting options.
   *
   * @var array
   */
  protected array $sorting = [];

  /**
   * Constructs a Sql object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\views\Plugin\views\query\DateSqlInterface $date_sql
   *   The database-specific date handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\views\ViewsData $viewsData
   *   The views data service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    DateSqlInterface $date_sql,
    MessengerInterface $messenger,
    protected ViewsData $viewsData,
  ) {
    // By default, use AND operator to connect WHERE groups.
    $this->groupOperator = 'AND';

    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $date_sql,
      $messenger
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('views.date_sql'),
      $container->get('messenger'),
      $container->get('views.views_data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ensureTable($table, $relationship = NULL, ?JoinPluginBase $join = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $alias = '', $params = []) {
    return $alias ? $alias : $field;
  }

  /**
   * Adds a condition.
   */
  public function condition($group, $field, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->commands[$group][] = [
      'method' => 'condition',
      'args' => [$field, $value, $operator, $langcode],
    ];
  }

  /**
   * Add's an exists command.
   */
  public function exists($group, $field, $langcode = NULL) {
    $this->commands[$group][] = [
      'method' => 'exists',
      'args' => [$field, $langcode],
    ];
  }

  /**
   * Add's an not exists command.
   */
  public function notExists($group, $field, $langcode = NULL) {
    $this->commands[$group][] = [
      'method' => 'notExists',
      'args' => [$field, $langcode],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    if ($alias) {
      $this->sorting[$alias] = $order;
    }
    elseif ($field) {
      $this->sorting[$field] = $order;
    }

  }

  /**
   * Executes query and fills the associated view object with according values.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->pager['current_page'].
   *
   * $view->result should contain an array of objects. The array must use a
   * numeric index starting at 0.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view which is executed.
   */
  public function execute(ViewExecutable $view) {
    $this->groupOperator = $this->groupOperator ?? 'AND';
    $base_table = $this->view->storage->get('base_table');
    $data = $this->viewsData->get($base_table);
    $entity_type = $data['table']['entity type'];
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery($this->groupOperator);
    $query->accessCheck();
    $this->entityConditionGroups = [
      $query,
    ];

    $this->buildConditions();
    $this->buildSorting($query);

    // Set up the pager and pass the pager state to the query.
    $view->initPager();
    $this->pager = $view->getPager();
    if ($this->pager->usesOptions()) {
      $query->pager($this->pager->options['items_per_page']);
    }

    $ids = $query->execute();
    $results = $storage->loadMultiple($ids);
    $index = 0;
    /** @var \Drupal\Core\Config\Entity\ConfigEntityBase $result */
    foreach ($results as $result) {
      // @todo toArray() doesn't return all properties.
      $entity = $result->toArray();
      $entity['type'] = $entity_type;
      $entity['entity'] = $result;
      // 'index' key is required.
      $entity['index'] = $index++;
      $view->result[] = new ResultRow($entity);
    }
    $view->total_rows = count($view->result);
    $view->execute_time = 0;
  }

  /**
   * Build conditions based on it's groups.
   */
  protected function buildConditions() {
    foreach ($this->commands as $group => $grouped_commands) {
      $conditionGroup = $this->getConditionGroup($group);
      foreach ($grouped_commands as $command) {
        call_user_func_array([$conditionGroup, $command['method']], $command['args']);
      }
    }
  }

  /**
   * Returns a condition group.
   */
  protected function getConditionGroup($group) {
    if (!isset($this->entityConditionGroups[$group])) {
      $query = $this->entityConditionGroups[0];
      $condition = isset($this->where[$group]) && $this->where[$group]['type'] == 'OR' ? $query->orConditionGroup() : $query->andConditionGroup();
      $query->condition($condition);
      $this->entityConditionGroups[$group] = $condition;
    }
    return $this->entityConditionGroups[$group];
  }

  /**
   * Adds sorting to query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to get configs.
   */
  protected function buildSorting(QueryInterface $query) {
    foreach ($this->sorting as $field => $direction) {
      $query->sort($field, $direction);
    }
  }

}
