<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_settings_form_test\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Provides a Scheme.org Blueprint settings custom test form.
 */
class SchemaDotOrgSettingsCustomTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_settings_form_custom_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['schemadotorg_settings_form_test'] = [
      '#tree' => TRUE,
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
        '#config_name' => 'schemadotorg_settings_form_test.settings',
        '#config_key' => $settings_type,
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save settings.
    $settings = $form_state->getValue('schemadotorg_settings_form_test');
    $this->configFactory()
      ->getEditable('schemadotorg_settings_form_test.settings')
      ->setData($settings)
      ->save();

    // Display the config data.
    $data = $this->config('schemadotorg_settings_form_test.settings')
      ->getRawData();
    unset($data['_core']);
    $this->messenger()->addStatus(Markup::create('<pre>' . Yaml::encode($data) . '</pre>'));

  }

}
