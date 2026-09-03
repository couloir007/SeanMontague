<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides context popup.
 */
#[MapFeature(
  id: 'context_popup',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Context Popup'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Provide context / right-click popup window.'),
  type: 'google_maps'
)]
class GoogleContextPopup extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'content' => [
        'value' => '',
        'format' => filter_default_format(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);
    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Context popup content'),
      '#description' => $this->t('A right click on the map will open a context popup with this content. Tokens supported. Additionally "@lat, @lng" will be replaced dynamically.'),
    ];
    if (!empty($settings['content']['value'])) {
      $form['content']['#default_value'] = $settings['content']['value'];
    }

    if (!empty($settings['content']['format'])) {
      $form['content']['#format'] = $settings['content']['format'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], ?MapProviderInterface $mapProvider = NULL): array {
    if (
      !empty($feature_settings['content']['value'])
      && !empty($feature_settings['content']['format'])
    ) {
      $feature_settings['content'] = check_markup($this->token->replace($feature_settings['content']['value'], $context), $feature_settings['content']['format']);
    }

    return parent::alterMap($render_array, $feature_settings, $context, $mapProvider);
  }

}
