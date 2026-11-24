<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_graphql\Functional;

use Drupal\Core\Form\FormState;
use Drupal\Tests\graphql_compose\Functional\GraphQLComposeBrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\graphql_compose\Form\SchemaForm;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the Custom Field subfield settings on the GraphQL Compose schema form.
 *
 * The form is submitted programmatically: the validation under test runs on
 * submitted values and does not depend on how the form renders.
 *
 * @group custom_field
 * @group custom_field_graphql
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_graphql')]
#[RunTestsInSeparateProcesses]
class CustomFieldGraphqlSchemaFormTest extends GraphQLComposeBrowserTestBase {

  /**
   * We aren't concerned with strict config schema for contrib.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE; // @phpcs:ignore

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field',
    'custom_field_test',
    'custom_field_graphql',
    'graphql_compose',
    'node',
    'user',
  ];

  /**
   * The error key of the test field's subfields container.
   *
   * @var string
   */
  protected string $errorKey = 'settings][field_config][node][custom_field_entity_test][field_test][subfields';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setEntityConfig('node', 'custom_field_entity_test', [
      'enabled' => TRUE,
    ]);
    $this->setFieldConfig('node', 'custom_field_entity_test', 'field_test', [
      'enabled' => TRUE,
    ]);
  }

  /**
   * Test an enabled field requires at least one enabled subfield.
   */
  public function testEnabledFieldRequiresSubfield(): void {
    $values = $this->getValues(TRUE, TRUE);
    $errors = $this->submitSchemaForm($values);
    $this->assertArrayHasKey($this->errorKey, $errors);
    $this->assertEquals('Custom Field Test must have at least one subfield enabled.', (string) $errors[$this->errorKey]);

    $values['settings']['field_config']['node']['custom_field_entity_test']['field_test']['subfields']['string']['enabled'] = 1;
    $this->assertArrayNotHasKey($this->errorKey, $this->submitSchemaForm($values));
  }

  /**
   * Test a disabled field or bundle is not validated.
   */
  public function testDisabledFieldOrBundleSkipsValidation(): void {
    // The field is disabled, but its subfield values are still submitted.
    $errors = $this->submitSchemaForm($this->getValues(TRUE, FALSE));
    $this->assertArrayNotHasKey($this->errorKey, $errors);

    // The field is enabled, but its bundle is disabled.
    $errors = $this->submitSchemaForm($this->getValues(FALSE, TRUE));
    $this->assertArrayNotHasKey($this->errorKey, $errors);
  }

  /**
   * Get schema form values with every subfield of the test field disabled.
   *
   * @param bool $bundle_enabled
   *   Whether the bundle is enabled.
   * @param bool $field_enabled
   *   Whether the field is enabled.
   *
   * @return array
   *   The form values.
   */
  protected function getValues(bool $bundle_enabled, bool $field_enabled): array {
    $field_config = FieldConfig::loadByName('node', 'custom_field_entity_test', 'field_test');
    $subfields = [];
    foreach (array_keys($field_config->getSetting('columns')) as $name) {
      // Unchecked checkboxes are submitted as NULL.
      $subfields[$name]['enabled'] = NULL;
    }

    $values['settings']['entity_config']['node']['custom_field_entity_test']['enabled'] = $bundle_enabled ? 1 : NULL;
    $values['settings']['field_config']['node']['custom_field_entity_test']['field_test'] = [
      'enabled' => $field_enabled ? 1 : NULL,
      'subfields' => $subfields,
    ];
    return $values;
  }

  /**
   * Submit the schema form programmatically.
   *
   * @param array $values
   *   The form values.
   *
   * @return array
   *   The form errors, keyed by element parents.
   */
  protected function submitSchemaForm(array $values): array {
    $form_state = (new FormState())->setValues($values);
    $this->container->get('form_builder')->submitForm(SchemaForm::class, $form_state);
    return $form_state->getErrors();
  }

}
