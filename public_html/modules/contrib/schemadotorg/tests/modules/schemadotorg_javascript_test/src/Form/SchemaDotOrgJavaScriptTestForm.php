<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_javascript_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\schemadotorg\Element\SchemaDotOrgAutocomplete;

/**
 * Provides a reusable form for Schema.org JavaScript testing.
 */
class SchemaDotOrgJavaScriptTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_javascript_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // @see \Drupal\Tests\schemadotorg\FunctionalJavascript\SchemaDotOrgAutocompleteJavaScriptTest
    $autocomplete_action = Url::fromRoute(
      'schemadotorg_javascript_test.form',
      [],
      ['query' => ['autocomplete' => '']],
    )->toString();
    $form['autocomplete'] = [
      '#type' => 'schemadotorg_autocomplete',
      '#title' => $this->t('Schema.org autocomplete'),
      '#target_type' => SchemaDotOrgAutocomplete::SCHEMA_TYPES,
      '#action' => $autocomplete_action,
    ];
    $form['autocomplete_dialog'] = [
      '#type' => 'schemadotorg_autocomplete',
      '#title' => $this->t('Schema.org autocomplete dialog'),
      '#target_type' => SchemaDotOrgAutocomplete::SCHEMA_TYPES,
      '#action' => $autocomplete_action,
      '#prefix' => '<div class="ui-dialog">',
      '#suffix' => '</div>',
    ];

    // @see \Drupal\Tests\schemadotorg\FunctionalJavascript\SchemaDotOrgDetailsJavaScriptTest
    $form['#attached']['library'][] = 'schemadotorg/schemadotorg.details';
    $form['details'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['region-help']],
    ];
    $form['details']['closed'] = [
      '#type' => 'details',
      '#title' => $this->t('Closed details'),
      '#attributes' => [
        'id' => 'schemadotorg-javascript-test-details-closed',
        'data-schemadotorg-details-key' => 'schemadotorg-javascript-test-details-closed',
      ],
      'content' => ['#markup' => $this->t('Closed details content.')],
    ];
    $form['details']['open'] = [
      '#type' => 'details',
      '#title' => $this->t('Open details'),
      '#open' => TRUE,
      '#attributes' => [
        'id' => 'schemadotorg-javascript-test-details-open',
        'data-schemadotorg-details-key' => 'schemadotorg-javascript-test-details-open',
      ],
      'content' => ['#markup' => $this->t('Open details content.')],
    ];
    $form['details']['hash'] = [
      '#type' => 'details',
      '#title' => $this->t('Hash details'),
      '#attributes' => [
        'id' => 'schemadotorg-javascript-test-details-hash',
        'data-schemadotorg-details-key' => 'schemadotorg-javascript-test-details-hash',
      ],
      'content' => ['#markup' => $this->t('Hash details content.')],
    ];

    // @see \Drupal\Tests\schemadotorg\FunctionalJavascript\SchemaDotOrgCodeMirrorJavaScriptTest
    $form['#attached']['library'][] = 'schemadotorg/codemirror.yaml';
    $form['codemirror'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Codemirror'),
    ];
    $form['codemirror']['codemirror_element'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CodeMirror YAML'),
      '#default_value' => "name: Example\ntype: Thing\n",
      '#attributes' => [
        'id' => 'schemadotorg-javascript-test-codemirror',
        'class' => ['schemadotorg-codemirror'],
        'data-mode' => 'text/x-yaml',
      ],
    ];
    $form['codemirror']['codemirror_example'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#plain_text' => "name: Example\ntype: Thing\n",
      '#attributes' => [
        'id' => 'schemadotorg-javascript-test-codemirror-example',
        'data-schemadotorg-codemirror-mode' => 'text/x-yaml',
      ],
    ];

    // @see \Drupal\Tests\schemadotorg\FunctionalJavascript\SchemaDotOrgSettingsElementJavaScriptTest
    $form['settings'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => $this->t('Schema.org settings'),
      '#id' => 'schemadotorg-javascript-test-settings',
      '#description' => $this->t('Schema.org settings description.'),
      '#default_value' => [
        'name' => 'Example',
        'type' => 'Thing',
      ],
      '#example' => "name: Example\ntype: Thing\n",
    ];

    // @see \Drupal\Tests\schemadotorg\FunctionalJavascript\SchemaDotOrgFormJavaScriptTest
    $form['#attached']['library'][] = 'schemadotorg/schemadotorg.form';
    $form['#attributes']['class'][] = 'js-schemadotorg-submit-once';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('<front>'),
      '#attributes' => ['id' => 'edit-cancel'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // This fixture form does not submit values.
  }

}
