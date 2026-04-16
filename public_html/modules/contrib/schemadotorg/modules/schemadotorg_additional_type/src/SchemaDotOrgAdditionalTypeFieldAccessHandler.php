<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_additional_type;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgMappingStorageTrait;

/**
 * The Schema.org additional type field access handler.
 */
class SchemaDotOrgAdditionalTypeFieldAccessHandler implements SchemaDotOrgAdditionalTypeFieldAccessHandlerInterface {
  use SchemaDotOrgMappingStorageTrait;

  /**
   * Cache entity rules by entity type and bundle.
   */
  protected array $rulesCache = [];

  /**
   * Cache additional type values by UUID.
   */
  protected array $additionalTypeCache = [];

  /**
   * Constructs a SchemaDotOrgAdditionalTypeFieldAccessHandler object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg_additional_type\SchemaDotOrgAdditionalTypeManagerInterface $schemadotorgAdditionalTypeManager
   *   The Schema.org additional type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgAdditionalTypeManagerInterface $schemadotorgAdditionalTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function entityFieldAccess(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResult {
    $rules = $this->getRules($operation, $field_definition);
    if (empty($rules)) {
      return AccessResult::neutral();
    }

    $entity = $items?->getEntity();
    $result = $this->checkRules($rules, $account, $entity);
    return AccessResult::forbiddenIf(!$result);
  }

  /**
   * Resets the cache for entity-related operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity for which the cache should be reset. If NULL, the entire
   *   cache is cleared. If it is a ContentEntityInterface, only the cache for
   *   that specific entity is cleared.
   *
   * @return void
   *   This method does not return a value.
   */
  public function resetCache(?EntityInterface $entity = NULL): void {
    if ($entity instanceof FieldDefinitionInterface || is_null($entity)) {
      $this->rulesCache = [];
    }
    elseif ($entity instanceof ContentEntityInterface) {
      unset($this->additionalTypeCache[$entity->uuid()]);
    }
  }

  /**
   * Get the field access rule for a specific operation and field definition.
   *
   * @param string $operation
   *   The operation to be performed.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of rules for the specific operation and field definition.
   */
  protected function getRules(string $operation, FieldDefinitionInterface $field_definition): array {
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    $field_name = $field_definition->getName();

    $parts = [];
    $parts['entity_type_id'] = $entity_type_id;
    $parts['bundle'] = $bundle;

    $rules_id = implode(':', array_merge($parts, [$field_name, $operation]));
    if (isset($this->rulesCache[$rules_id])) {
      return $this->rulesCache[$rules_id];
    }

    $mapping = $this->loadMapping($entity_type_id, $bundle);
    $schema_type = $mapping?->getSchemaType();
    $schema_property = $mapping?->getSchemaPropertyMapping($field_name);
    $parts['schema_type'] = $schema_type;

    $field_access = $this->configFactory
      ->get('schemadotorg_additional_type.settings')
      ->get('field_access');
    $entity_rules = $this->schemaTypeManager->getSetting($field_access, $parts, ['multiple' => TRUE]) ?? [];

    $operations = array_flip(['any', $operation]);
    $rules = [];
    foreach ($entity_rules as $fields) {
      $fields = $this->parseSwitchCase($fields);
      $rule = $fields[$field_name] ?? $fields[$schema_property] ?? [];
      $rule = array_intersect_key($rule, $operations);
      if ($rule) {
        $rules[] = $rule;
      }
    }

    $this->rulesCache[$rules_id] = $rules;
    return $rules;
  }

  /**
   * Parse switch case in YAML.
   *
   * Loop through $config (in reverse) so that we can set fields that are empty
   * strings using the next value.
   *
   * This trick/hack allows for switch/case like fall through behavior in
   * YAML files (http://en.wikipedia.org/wiki/Switch_statement#Fallthrough)
   * and relies on YAML's support for key orders.
   * (http://www.yaml.org/spec/1.2/spec.html#id2765608)
   *
   * @param array $config
   *   An associative array of key/value pairs parsed from a YAML file.
   *
   * @return array
   *   Empty values populated by the next value.
   */
  public static function parseSwitchCase(array $config): array {
    $config = array_reverse($config, TRUE);
    $previous_key = NULL;
    foreach ($config as $key => $value) {
      if (empty($value) && $previous_key) {
        $config[$key] = $config[$previous_key];
      }
      $previous_key = $key;
    }
    $config = array_reverse($config, TRUE);
    return $config;
  }

  /**
   * Evaluate rules.
   *
   * @param array $rules
   *   An array of rules to evaluate.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to check access.
   *
   * @return bool
   *   TRUE if all rules are satisfied, FALSE otherwise.
   */
  protected function checkRules(array $rules, AccountInterface $account, ?EntityInterface $entity = NULL): bool {
    $results = [];
    foreach ($rules as $rule) {
      $results[] = $this->checkRule($rule, $account, $entity);
    }
    return (array_sum($results) === count($results));
  }

  /**
   * Evaluate a rule.
   *
   * @param array $rule
   *   The rule to evaluate.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to check access.
   *
   * @return bool
   *   TRUE if all rule conditions are satisfied, FALSE otherwise.
   */
  protected function checkRule(array $rule, AccountInterface $account, ?EntityInterface $entity = NULL): bool {
    $results = [];
    foreach ($rule as $conditions) {
      $results[] = $this->checkConditions($conditions, $account, $entity);
    }
    return (array_sum($results) === count($results));
  }

  /**
   * Evaluate conditions.
   *
   * @param array $conditions
   *   The conditions to evaluate.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to check access.
   *
   * @return bool
   *   TRUE if all conditions are satisfied, FALSE otherwise.
   */
  protected function checkConditions(array $conditions, AccountInterface $account, ?EntityInterface $entity = NULL): bool {
    $operator = $conditions['operator'] ?? 'and';
    unset($conditions['operator']);

    $results = [];
    foreach ($conditions as $condition => $values) {
      if (str_starts_with($condition, '!')) {
        $condition = substr($condition, 1);
        $expected = FALSE;
      }
      else {
        $expected = TRUE;
      }

      $result = $this->checkCondition($condition, $values, $account, $entity);
      $results[$condition] = ($result === $expected);
    }

    return ($operator === 'or')
      ? (bool) array_sum($results)
      : (array_sum($results) === count($results));
  }

  /**
   * Evaluate condition.
   *
   * @param string $condition
   *   The condition to evaluate.
   * @param array $values
   *   The values to evaluate against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to check access.
   *
   * @return bool
   *   TRUE if all conditions are satisfied, FALSE otherwise.
   */
  protected function checkCondition(string $condition, array $values, AccountInterface $account, ?EntityInterface $entity = NULL): bool {
    switch ($condition) {
      // Check entity additional types condition.
      case 'additional_types':
        $additional_type = $this->getAdditionalType($entity) ?? '';
        return in_array($additional_type, $values)
          || in_array(strtolower($additional_type), $values);

      // Check entity bundles condition.
      case 'bundles':
        if (!$entity instanceof ContentEntityInterface) {
          return TRUE;
        }
        return in_array($entity->bundle(), $values);

      // Check entity access condition.
      case 'entity_access':
        if (!$entity || !method_exists($entity, 'access')) {
          return TRUE;
        }

        foreach ($values as $operation) {
          if ($entity->access($operation, $account)) {
            return TRUE;
          }
        }
        return FALSE;

      // Check entity Schema.org type condition..
      case 'schema_types':
        if (!$entity) {
          return TRUE;
        }
        $mapping = $this->getMappingStorage()->loadByEntity($entity);
        $schema_types = ($mapping)
          ? $this->schemaTypeManager->getParentTypes($mapping->getSchemaType())
          : [];
        return (bool) array_intersect($schema_types, $values);

      // Check user account permissions.
      case 'permissions':
        foreach ($values as $permission) {
          if ($account->hasPermission($permission)) {
            return TRUE;
          }
        }
        return FALSE;

      // Check user account roles.
      case 'roles':
        return (bool) array_intersect($account->getRoles(), $values);

      default:
        return TRUE;
    }
  }

  /**
   * Retrieves the additional type for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   An entity.
   *
   * @return string|null
   *   The additional type associated with the entity or NULL if not available.
   */
  protected function getAdditionalType(?EntityInterface $entity): ?string {
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }

    $uuid = $entity->uuid();
    if (isset($this->additionalTypeCache[$uuid])) {
      return $this->additionalTypeCache[$uuid];
    }

    $additional_type = NULL;
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if ($mapping) {
      $field_name = $mapping->getSchemaPropertyFieldName('additionalType');
      if ($entity->hasField($field_name)) {
        $additional_type = $entity->get($field_name)->value;
      }
    }

    $this->additionalTypeCache[$uuid] = $additional_type;
    return $additional_type;
  }

}
