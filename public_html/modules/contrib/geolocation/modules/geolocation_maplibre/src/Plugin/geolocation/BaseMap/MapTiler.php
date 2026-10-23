<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\BaseMap;

use Drupal\geolocation_maplibre\Attribute\BaseMap;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation_maplibre\BaseMapBase;

/**
 * Provides MapTiler base maps.
 */
#[BaseMap(
  id: 'maptiler',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('MapTiler'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.maptiler.com/. Requires an API key.')
)]
class MapTiler extends BaseMapBase {

  /**
   * {@inheritdoc}
   */
  protected array $styles = [
    'streets-v2' => 'https://api.maptiler.com/maps/streets-v2/style.json',
    'basic-v2' => 'https://api.maptiler.com/maps/basic-v2/style.json',
    'bright-v2' => 'https://api.maptiler.com/maps/bright-v2/style.json',
    'outdoor-v2' => 'https://api.maptiler.com/maps/outdoor-v2/style.json',
    'topo-v2' => 'https://api.maptiler.com/maps/topo-v2/style.json',
    'satellite' => 'https://api.maptiler.com/maps/satellite/style.json',
    'dataviz' => 'https://api.maptiler.com/maps/dataviz/style.json',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'style' => 'streets-v2',
      'key' => '',
      'use_current_language' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $form = parent::getSettingsForm($settings, $parents);

    $settings = $this->getSettings($settings);

    $form['use_current_language'] = $this->languageCheckbox(
      $settings,
      $this->t('Label the map in the interface language. MapTiler serves the OpenMapTiles schema, which carries every name OpenStreetMap has for a place.')
    );

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $settings['key'],
      '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
        '@key' => $this->t('API key'),
        '@url' => 'https://www.maptiler.com/',
        '@provider' => 'MapTiler',
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleUrl(array $settings): string {
    $settings = $this->getSettings($settings);

    $url = parent::getStyleUrl($settings);

    if ($settings['key']) {
      $url .= '?key=' . urlencode($settings['key']);
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $settings, MapProviderInterface $map_provider): array {
    $render_array = parent::alterMap($render_array, $settings, $map_provider);

    if (!($this->getSettings($settings)['use_current_language'] ?? FALSE)) {
      return $render_array;
    }

    // The OpenMapTiles schema keys a translated name "name:de".
    $langcode = $this->languageCode(\Drupal::languageManager()->getCurrentLanguage()->getId());

    return $this->attachSettings($render_array, ['base_map' => ['settings' => ['language_fields' => ['name:' . $langcode]]]]);
  }

}
