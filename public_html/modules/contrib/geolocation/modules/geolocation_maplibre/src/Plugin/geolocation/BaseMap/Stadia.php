<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\BaseMap;

use Drupal\geolocation_maplibre\Attribute\BaseMap;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation_maplibre\BaseMapBase;

/**
 * Provides Stadia Maps base maps.
 */
#[BaseMap(
  id: 'stadia',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Stadia Maps'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://stadiamaps.com/. Requires an API key, or a registered domain.')
)]
class Stadia extends BaseMapBase {

  /**
   * {@inheritdoc}
   *
   * The same styles the Stadia tile layer provider serves as raster.
   */
  protected array $styles = [
    'alidade_smooth' => 'https://tiles.stadiamaps.com/styles/alidade_smooth.json',
    'alidade_smooth_dark' => 'https://tiles.stadiamaps.com/styles/alidade_smooth_dark.json',
    'outdoors' => 'https://tiles.stadiamaps.com/styles/outdoors.json',
    'osm_bright' => 'https://tiles.stadiamaps.com/styles/osm_bright.json',
    'stamen_toner' => 'https://tiles.stadiamaps.com/styles/stamen_toner.json',
    'stamen_toner_lite' => 'https://tiles.stadiamaps.com/styles/stamen_toner_lite.json',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'style' => 'alidade_smooth',
      'api_key' => '',
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
      $this->t('Label the map in the interface language. Stadia Maps serves the OpenMapTiles schema, which carries every name OpenStreetMap has for a place.')
    );

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $settings['api_key'],
      '#description' => $this->t('Get your @key here <a href="@url">@provider</a>. Optional where the domain is registered with Stadia instead.', [
        '@key' => $this->t('API key'),
        '@url' => 'https://stadiamaps.com/',
        '@provider' => 'Stadia Maps',
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

    if ($settings['api_key']) {
      $url .= '?api_key=' . urlencode($settings['api_key']);
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
