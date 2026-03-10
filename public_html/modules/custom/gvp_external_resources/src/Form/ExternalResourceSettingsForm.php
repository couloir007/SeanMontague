<?php

namespace Drupal\gvp_external_resources\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Placeholder settings form for Field UI base routes and admin tabs.
 *
 * This module currently stores configuration on the entities themselves.
 * Keeping a settings route allows Field UI "Manage fields/display" to work
 * consistently for custom content entities.
 */
class ExternalResourceSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'gvp_external_resources_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['message'] = [
      '#type' => 'item',
      '#title' => $this->t('Settings'),
      '#markup' => $this->t('No module-level settings yet. Use Field UI tabs (Manage fields / Manage form display / Manage display) on each entity type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Intentionally empty.
  }

}
