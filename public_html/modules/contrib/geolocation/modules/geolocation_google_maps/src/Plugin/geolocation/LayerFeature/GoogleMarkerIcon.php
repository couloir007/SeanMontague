<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\LayerFeatureInterface;
use Drupal\geolocation\MapProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides marker icon adjustment.
 */
#[LayerFeature(
  id: 'marker_icon',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Icon Adjustment'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Replace the default marker pin by a custom image.'),
  type: 'google_maps'
)]
class GoogleMarkerIcon extends LayerFeatureBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $moduleHandler,
    FileSystemInterface $fileSystem,
    Token $token,
    LibraryDiscoveryInterface $libraryDiscovery,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $moduleHandler, $fileSystem, $token, $libraryDiscovery);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LayerFeatureInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('library.discovery'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'marker_icon_path' => '',
      'size' => [
        'width' => NULL,
        'height' => NULL,
      ],
      'anchor' => [
        'left' => '',
        'top' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], ?MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['marker_icon_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon path'),
      '#description' => $this->t('Set relative or absolute path to custom marker icon. Tokens supported. Empty for default. Attention: In views contexts, additional icon source options are available in the style settings, which take precedence over this value.'),
      '#default_value' => $settings['marker_icon_path'],
    ];

    $form['size'] = [
      '#type' => 'item',
      '#description' => $this->t('Display size of the icon in pixels. Leave empty to use the intrinsic size of the image. Set only one value to scale proportionally.'),
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Size - Width'),
        '#default_value' => $settings['size']['width'],
        '#min' => 0,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Size - Height'),
        '#default_value' => $settings['size']['height'],
        '#min' => 0,
      ],
    ];

    $form['anchor'] = [
      '#type' => 'item',
      '#description' => $this->t('The point of the icon anchored to the location, as an offset from the top left corner of the image. CSS length-percentage values, like "-50%" or "-16px". Empty for the Google Maps defaults of "-50%" (horizontally centered) and "-100%" (bottom edge).'),
      'left' => [
        '#type' => 'textfield',
        '#title' => $this->t('Anchor - Left'),
        '#default_value' => $settings['anchor']['left'],
        '#size' => 10,
        '#pattern' => '-?[0-9]*\.?[0-9]+(%|px|em|rem)',
      ],
      'top' => [
        '#type' => 'textfield',
        '#title' => $this->t('Anchor - Top'),
        '#default_value' => $settings['anchor']['top'],
        '#size' => 10,
        '#pattern' => '-?[0-9]*\.?[0-9]+(%|px|em|rem)',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {
    if (!empty($feature_settings['marker_icon_path'])) {
      $icon_path = $this->token->replace($feature_settings['marker_icon_path'], $context);
      $feature_settings['marker_icon_path'] = $this->fileUrlGenerator->generateString($icon_path);
    }

    return parent::alterLayer($render_array, $layer_id, $feature_settings, $context);
  }

}
