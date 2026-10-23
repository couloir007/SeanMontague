<?php

namespace Drupal\geolocation_maplibre\Plugin\geolocation\BaseMap;

use Drupal\geolocation_maplibre\Attribute\BaseMap;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation_maplibre\BaseMapBase;

/**
 * Provides OpenFreeMap base maps.
 */
#[BaseMap(
  id: 'openfreemap',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('OpenFreeMap'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('See https://openfreemap.org/. Free, no account and no API key.')
)]
class OpenFreeMap extends BaseMapBase {

  /**
   * {@inheritdoc}
   */
  protected array $styles = [
    'liberty' => 'https://tiles.openfreemap.org/styles/liberty',
    'bright' => 'https://tiles.openfreemap.org/styles/bright',
    'positron' => 'https://tiles.openfreemap.org/styles/positron',
    'dark' => 'https://tiles.openfreemap.org/styles/dark',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return ['style' => 'liberty', 'use_current_language' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $form = parent::getSettingsForm($settings, $parents);

    $form['use_current_language'] = $this->languageCheckbox(
      $this->getSettings($settings),
      $this->t('Label the map in the interface language. OpenFreeMap serves the OpenMapTiles schema, which carries every name OpenStreetMap has for a place.')
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleOptions(): array {
    return [
      'liberty' => $this->t('Liberty'),
      'bright' => $this->t('Bright'),
      'positron' => $this->t('Positron'),
      'dark' => $this->t('Dark'),
    ];
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
