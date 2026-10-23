<?php

namespace Drupal\geolocation_maplibre;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\MapProviderInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Provide a base map base class.
 */
abstract class BaseMapBase extends PluginBase implements BaseMapInterface {

  use TranslatorTrait;

  /**
   * Style URL per style option.
   *
   * Keyed by the option ID offered in the settings form. A service with a
   * single style leaves this alone and overrides getStyleUrl() instead.
   *
   * @var string[]
   */
  protected array $styles = [];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings): array {
    $default_settings = static::getDefaultSettings();
    $settings = array_replace_recursive($default_settings, $settings);

    return array_intersect_key($settings, $default_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $settings): array {
    return [
      $this->t('Base map: @base_map', ['@base_map' => $this->getPluginDefinition()['name']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $settings = $this->getSettings($settings);

    $form = [];

    if (count($this->styles) > 1) {
      $form['style'] = [
        '#type' => 'select',
        '#title' => $this->t('Style'),
        '#options' => $this->getStyleOptions(),
        '#default_value' => $settings['style'] ?? array_key_first($this->styles),
      ];
    }

    return $form;
  }

  /**
   * Get the styles this service offers, for the settings form.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Style labels, keyed by option ID.
   */
  public function getStyleOptions(): array {
    return array_combine(array_keys($this->styles), array_keys($this->styles));
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleUrl(array $settings): string {
    $settings = $this->getSettings($settings);

    return $this->styles[$settings['style'] ?? ''] ?? (string) reset($this->styles);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribution(array $settings): string {
    return '';
  }

  /**
   * Get the language code to look for labels in.
   *
   * Drupal stores language codes as lowercased IETF tags - "de", "pt-br" -
   * while tile schemas key their names by the two-letter primary subtag. Core
   * has no API for taking a tag apart and the intl extension is neither
   * required by core nor covered by the polyfills core ships, so it is used
   * where a site has it and the tag is split where it does not.
   *
   * @param string $langcode
   *   Drupal language code.
   *
   * @return string
   *   The primary subtag, for example "pt" from "pt-br".
   */
  protected function languageCode(string $langcode): string {
    if (extension_loaded('intl')) {
      return \Locale::getPrimaryLanguage($langcode) ?: $langcode;
    }

    return explode('-', $langcode)[0];
  }

  /**
   * Build the language checkbox a localizable base map offers.
   *
   * @param array $settings
   *   Settings.
   * @param string $description
   *   What the service can and cannot localize.
   *
   * @return array
   *   Form element.
   */
  protected function languageCheckbox(array $settings, string $description): array {
    return [
      '#type' => 'checkbox',
      '#title' => $this->t('Use current user language'),
      '#description' => $description,
      '#default_value' => $settings['use_current_language'] ?? FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $settings, MapProviderInterface $map_provider): array {
    return $this->attachSettings($render_array, [
      'style_url' => $this->getStyleUrl($settings),
      'attribution' => $this->getAttribution($settings),
      'base_map' => [
        'import_path' => $this->getJavascriptModulePath(),
        'settings' => [],
      ],
    ]);
  }

  /**
   * Get the path of the Javascript module this base map runs, if it has one.
   *
   * A base map that needs something of the renderer - a service whose URLs
   * have to be rewritten, tiles whose labels can be relabelled - ships it as
   * a module beside the plugin, named after the class. Most need nothing.
   *
   * @return string|null
   *   Javascript module path, or NULL where the plugin ships none.
   */
  public function getJavascriptModulePath(): ?string {
    $class_name = (new \ReflectionClass($this))->getShortName();
    $module_path = \Drupal::moduleHandler()->getModule($this->getPluginDefinition()['provider'])->getPath();

    if (!file_exists(\Drupal::service('file_system')->realpath($module_path) . '/js/BaseMap/' . $class_name . '.js')) {
      return NULL;
    }

    return base_path() . $module_path . '/js/BaseMap/' . $class_name . '.js';
  }

  /**
   * Attach settings for the map's Javascript.
   *
   * @param array $render_array
   *   Render array.
   * @param array $settings
   *   Settings to merge into those of the map.
   *
   * @return array
   *   Render array.
   */
  protected function attachSettings(array $render_array, array $settings): array {
    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                'maplibre_settings' => $settings,
              ],
            ],
          ],
        ],
      ]
    );

    return $render_array;
  }

}
