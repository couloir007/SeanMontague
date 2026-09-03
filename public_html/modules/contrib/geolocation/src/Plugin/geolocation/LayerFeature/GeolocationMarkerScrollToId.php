<?php

namespace Drupal\geolocation\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\Core\Template\Attribute;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides map tilt.
 */
#[LayerFeature(
  id: 'geolocation_marker_scroll_to_id',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Scroll-to-ID'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Clicking on a marker will try to scroll to the respective ID.'),
  type: 'all'
)]
class GeolocationMarkerScrollToId extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'scroll_target_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['scroll_target_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scroll target ID'),
      '#description' => $this->t('ID to scroll to on click. Tokens supported.'),
      '#default_value' => $settings['scroll_target_id'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {
    $render_array = parent::alterLayer($render_array, $layer_id, $feature_settings, $context);

    if (empty($render_array['#children']['locations'])) {
      return $render_array;
    }

    if (!empty($context['view'])) {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $context['view'];
    }

    foreach ($render_array['#children']['locations'] as &$location) {
      $scroll_target_id = $this->token->replace($feature_settings['scroll_target_id'], $context);

      if (empty($view)) {
        continue;
      }

      if (empty($location['#attributes'])) {
        $location['#attributes'] = [];
      }
      elseif (!is_array($location['#attributes'])) {
        $location['#attributes'] = new Attribute($location['#attributes']);
        $location['#attributes'] = $location['#attributes']->toArray();
      }

      if (isset($location['#attributes']['data-views-row-index'])) {
        $scroll_target_id = $view->getStyle()->tokenizeValue($scroll_target_id, (int) $location['#attributes']['data-views-row-index']);
        $location['#attributes']['data-scroll-target-id'] = $scroll_target_id;
      }
    }

    return $render_array;
  }

}
