<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_settings_form_test\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\schemadotorg\Form\SchemaDotOrgSettingsFormBase;

/**
 * Provides a Scheme.org Blueprint settings custom test form.
 */
class SchemaDotOrgSettingsSimpleTestForm extends SchemaDotOrgSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_settings_form_test.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_settings_form_simple_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Render the form without visible elements.
    if ($this->getRequest()->get('no_elements')) {
      $form['test'] = [
        '#type' => 'textfield',
        '#access' => FALSE,
      ];
      return parent::buildForm($form, $form_state);
    }

    $form['schemadotorg_settings_form_test'] = [
      '#tree' => TRUE,
    ];

    // Create examples of supported elements.
    $form['schemadotorg_settings_form_test']['textfield'] = [
      '#type' => 'textfield',
      '#title' => 'textfield',
    ];
    $form['schemadotorg_settings_form_test']['checkbox'] = [
      '#type' => 'checkbox',
      '#title' => 'checkbox',
    ];
    $form['schemadotorg_settings_form_test']['checkboxes'] = [
      '#type' => 'checkboxes',
      '#title' => 'checkboxes',
      '#options' => [
        'one' => $this->t('One'),
        'two' => $this->t('Two'),
        'three' => $this->t('Three'),
      ],
    ];

    // Create examples of all settings types.
    $settings_types = [
      'indexed',
      'indexed_grouped',
      'indexed_grouped_named',
      'associative',
      'associative_grouped',
      'associative_grouped_named',
      'links_grouped',
      'associative_advanced',
      'yaml',
      'yaml_raw',
      'json_raw',
    ];
    foreach ($settings_types as $settings_type) {
      $form['schemadotorg_settings_form_test'][$settings_type] = [
        '#type' => 'schemadotorg_settings',
        '#title' => $settings_type,
        '#mode' => str_starts_with($settings_type, 'json') ? 'json' : 'yaml',
        '#raw' => str_ends_with($settings_type, '_raw'),
      ];
    }

    // Add 'Browse Schema.org types.' to the first element.
    $form['schemadotorg_settings_form_test']['indexed']['#description_link'] = 'types';
    $form['schemadotorg_settings_form_test']['indexed']['#token_link'] = TRUE;

    // Add an example to the first element.
    $form['schemadotorg_settings_form_test']['indexed']['#example'] = '- one
- two
- three';

    // Add apply.
    $form['schemadotorg_settings_form_test']['apply'] = [];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Display the config data.
    $data = $this
      ->config('schemadotorg_settings_form_test.settings')
      ->getRawData();
    unset($data['_core']);
    $this->messenger()->addStatus(Markup::create('<pre>' . Yaml::encode($data) . '</pre>'));

    // Trigger the default configuration form submit behavior.
    parent::submitForm($form, $form_state);
  }

}
