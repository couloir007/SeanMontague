<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_field_validation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;

/**
 * The Schema.org field validation manager.
 */
class SchemaDotOrgFieldValidationManager implements SchemaDotOrgFieldValidationManagerInterface {

  /**
   * Constructs a SchemaDotOrgFieldValidationManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  public function fieldConfigInsert(FieldConfigInterface $field_config): void {
    if ($field_config->isSyncing()) {
      return;
    }

    if (empty($field_config->schemaDotOrgField) || $field_config->getType() !== 'string') {
      return;
    }

    $field_name = $field_config->getName();
    $schema_property = $field_config->schemaDotOrgField['schema_property'];

    $validation_rules = $this->configFactory
      ->get('schemadotorg_field_validation.settings')
      ->get('rules');
    $schema_validation_rule = $validation_rules[$schema_property]
      ?? $validation_rules[$field_name]
      ?? NULL;
    if (!$schema_validation_rule) {
      return;
    }

    $entity_type_id = $field_config->getTargetEntityTypeId();
    $bundle = $field_config->getTargetBundle();
    $field_label = $field_config->getLabel();

    // Load or create validation rule set.
    // @see \Drupal\field_validation\Form\FieldValidationRuleSetAddForm::validateForm
    $ruleset_storage = $this->entityTypeManager
      ->getStorage('field_validation_rule_set');
    $ruleset_name = "{$entity_type_id}_{$bundle}";
    /** @var \Drupal\field_validation\FieldValidationRuleSetInterface $ruleset */
    $ruleset = $ruleset_storage->load($ruleset_name) ?? $ruleset_storage->create([
      'name' => $ruleset_name,
      'label' => "$entity_type_id $bundle validation",
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
    ]);

    // Make sure the validation rule does not already exist.
    $validation_rules = $ruleset->getFieldValidationRules();
    foreach ($validation_rules as $validation_rule) {
      $configuration = $validation_rule->getConfiguration();
      if (
        $configuration['id'] === 'regex_constraint_rule'
        && $configuration['field_name'] === $field_name
        && $configuration['data']['pattern'] === $schema_validation_rule['pattern']
        && $configuration['data']['message'] === $schema_validation_rule['message']
      ) {
        return;
      }
    }

    // Add regex validation rule.
    // @see \Drupal\field_validation\Plugin\FieldValidationRule\RegexFieldValidationRule
    $ruleset->addFieldValidationRule([
      'id' => 'regex_constraint_rule',
      'title' => "Schema.org: $field_label",
      'field_name' => $field_name,
      'data' => [
        'validate_mode' => 'default',
        'pattern' => $schema_validation_rule['pattern'],
        'message' => $schema_validation_rule['message'],
      ],
      'column' => 'value',
      'weight' => 0,
    ]);

    $ruleset->save();
  }

}
