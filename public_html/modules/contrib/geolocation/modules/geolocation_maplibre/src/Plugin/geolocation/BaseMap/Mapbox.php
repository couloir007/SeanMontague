<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\BaseMap;

use Drupal\geolocation_maplibre\Attribute\BaseMap;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation_maplibre\BaseMapBase;

/**
 * Provides Mapbox base maps.
 */
#[BaseMap(
  id: 'mapbox',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Mapbox'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://www.mapbox.com/. Requires an access token. Billed per vector tile request rather than per map load, as the map is not drawn by Mapbox GL JS.')
)]
class Mapbox extends BaseMapBase {

  /**
   * Mapbox styles, as owner and style ID.
   *
   * @var string[]
   */
  protected array $styles = [
    'streets-v12' => 'mapbox/streets-v12',
    'outdoors-v12' => 'mapbox/outdoors-v12',
    'light-v11' => 'mapbox/light-v11',
    'dark-v11' => 'mapbox/dark-v11',
    'satellite-v9' => 'mapbox/satellite-v9',
    'satellite-streets-v12' => 'mapbox/satellite-streets-v12',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'style' => 'streets-v12',
      'custom_style' => '',
      'access_token' => '',
      'use_current_language' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleOptions(): array {
    return [
      'streets-v12' => $this->t('Streets'),
      'outdoors-v12' => $this->t('Outdoors'),
      'light-v11' => $this->t('Light'),
      'dark-v11' => $this->t('Dark'),
      'satellite-v9' => $this->t('Satellite'),
      'satellite-streets-v12' => $this->t('Satellite Streets'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $form = parent::getSettingsForm($settings, $parents);

    $settings = $this->getSettings($settings);

    $form['custom_style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mapbox Studio style'),
      '#default_value' => $settings['custom_style'],
      '#description' => $this->t('A style of your own as owner and style ID, for example <em>yourname/clx1234567890</em>. Overrides the selection above. A style built per language is how a Mapbox map is localized.'),
    ];

    $form['use_current_language'] = $this->languageCheckbox(
      $settings,
      $this->t('Label the map in the interface language. Mapbox Streets carries thirteen languages and leaves the local name in place for any other.')
    );

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#default_value' => $settings['access_token'],
      '#description' => $this->t('Get your @key here <a href="@url">@provider</a>.', [
        '@key' => $this->t('Access token'),
        '@url' => 'https://www.mapbox.com/',
        '@provider' => 'Mapbox',
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleUrl(array $settings): string {
    $settings = $this->getSettings($settings);

    $style = $settings['custom_style'] ?: ($this->styles[$settings['style']] ?? reset($this->styles));

    // The style document is fetched over HTTPS, but the sources, sprites and
    // fonts it names are mapbox:// URLs that only Mapbox GL JS resolves - the
    // map rewrites those as it loads them.
    $url = 'https://api.mapbox.com/styles/v1/' . trim($style, '/');

    if ($settings['access_token']) {
      $url .= '?access_token=' . urlencode($settings['access_token']);
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

    // Mapbox Streets separates the subtag with an underscore, not a colon.
    $langcode = $this->languageCode(\Drupal::languageManager()->getCurrentLanguage()->getId());

    return $this->attachSettings($render_array, ['base_map' => ['settings' => ['language_fields' => ['name_' . $langcode]]]]);
  }

}
