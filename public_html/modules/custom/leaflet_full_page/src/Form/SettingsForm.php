<?php

namespace Drupal\leaflet_full_page\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration settings form for the Leaflet Full Page module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'leaflet_full_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['leaflet_full_page.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('leaflet_full_page.settings');

    $form['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default geo_entity bundle'),
      '#description' => $this->t('Machine name of the geo_entity bundle to query when no bundle is supplied in the endpoint URL.'),
      '#default_value' => $config->get('bundle') ?? '',
    ];

    $form['geo_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Geo field'),
      '#description' => $this->t('Field name of the Geofield on the entity (e.g. <code>location</code>, <code>field_geo</code>).'),
      '#default_value' => $config->get('geo_field') ?? 'location',
    ];

    $form['endpoint_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint path'),
      '#description' => $this->t('URL path for the GeoJSON endpoint. Passed to JS as <code>drupalSettings.leaflet_full_page.itemsEndpoint</code>. Appending a bundle name is optional (e.g. <code>/leaflet-full-page/map-items/poi</code>).'),
      '#default_value' => $config->get('endpoint_path') ?? '/leaflet-full-page/map-items',
    ];

    $field_map = $config->get('field_map') ?? [];
    $form['field_map'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field map (JSON)'),
      '#description' => $this->t('Maps JSON property keys to entity field names. Simple form: <code>{"description": "field_body"}</code>. With view mode: <code>{"image": {"field": "field_listing_image_media", "view_mode": "default"}}</code>'),
      '#default_value' => empty($field_map) ? '' : json_encode($field_map, JSON_PRETTY_PRINT),
      '#rows' => 8,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $raw = trim($form_state->getValue('field_map'));
    if (!empty($raw)) {
      json_decode($raw, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('field_map', $this->t('Field map must be valid JSON: @error', [
          '@error' => json_last_error_msg(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $raw = trim($form_state->getValue('field_map'));
    $field_map = empty($raw) ? [] : (json_decode($raw, TRUE) ?? []);

    $this->config('leaflet_full_page.settings')
      ->set('bundle', trim($form_state->getValue('bundle')))
      ->set('geo_field', trim($form_state->getValue('geo_field')) ?: 'location')
      ->set('endpoint_path', trim($form_state->getValue('endpoint_path')) ?: '/leaflet-full-page/map-items')
      ->set('field_map', $field_map)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
