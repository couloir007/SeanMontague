<?php

declare(strict_types=1);

namespace Drupal\Tests\local_tasks_more\Functional;

use Drupal\Core\Form\FormState;
use Drupal\Tests\BrowserTestBase;
use Drupal\local_tasks_more\Form\LocalTasksMoreSettingsForm;

/**
 * Tests the Local Tasks More settings form.
 *
 * @group local_tasks_more
 *
 * @covers \Drupal\local_tasks_more\Form\LocalTasksMoreSettingsForm
 */
final class LocalTasksMoreSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'local_tasks_more'];

  /**
   * Test settings form.
   */
  public function testSettingsForm(): void {
    $assert = $this->assertSession();
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    // Get the settings form.
    $this->drupalGet('/admin/config/user-interface/local-tasks-more');

    // Check base route information.
    $assert->responseContains('entity.node_type.collection<br />entity.node_type.edit_form<br />');

    // Check local task information.
    $assert->responseContains('<pre># entity.node.canonical
entity.node.canonical:
  title: View
  weight: 0
entity.node.edit_form:
  title: Edit
  weight: 0
entity.node.delete_form:
  title: Delete
  weight: 10
entity.node.version_history:
  title: Revisions
  weight: 20
</pre>');

    // Check validating an element containing valid YAML data.
    $element = [
      '#parents' => ['alter_local_tasks'],
      '#title' => 'Local tasks alterations (YAML)',
      '#value' => 'valid yaml',
    ];
    $form_state = new FormState();
    $completed_form = [];
    LocalTasksMoreSettingsForm::alterLocalTasksElementValidate($element, $form_state, $completed_form);
    $this->assertNull($form_state->getError($element));

    // Check validating an element containing invalid YAML data.
    $element = [
      '#parents' => ['alter_local_tasks'],
      '#title' => 'Local tasks alterations (YAML)',
      '#value' => '"not: valid yaml',
    ];
    $form_state = new FormState();
    $completed_form = [];
    LocalTasksMoreSettingsForm::alterLocalTasksElementValidate($element, $form_state, $completed_form);
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $error */
    $error = $form_state->getError($element);
    $this->assertEquals('@name field is not valid YAML. %error', $error->getUntranslatedString());

    // Check converting an array of items into a YAML string based on configuration.
    $items = [
      [
        'plugin_id' => 'entity.node.delete_form',
        'status' => FALSE,
      ],
      [
        'plugin_id' => 'convert_bundles.entities:entity.node.convert_bundles',
        'title' => 'Convert',
      ],
    ];
    $expected_yaml = "entity.node.delete_form: false
'convert_bundles.entities:entity.node.convert_bundles':
  title: Convert
";
    $actual_yaml = LocalTasksMoreSettingsForm::alterLocalTasksFromConfig($items);
    $this->assertEquals($expected_yaml, $actual_yaml);

    // Check converting a YAML string to an array configuration.
    $yaml = "entity.node.delete_form: false
'convert_bundles.entities:entity.node.convert_bundles':
  title: Convert";
    $expected_items = [
      [
        'plugin_id' => 'entity.node.delete_form',
        'status' => FALSE,
      ],
      [
        'plugin_id' => 'convert_bundles.entities:entity.node.convert_bundles',
        'title' => 'Convert',
      ],
    ];
    $actual_items = LocalTasksMoreSettingsForm::alterLocalTasksToConfig($yaml);
    $this->assertEquals($expected_items, $actual_items);
  }

}
