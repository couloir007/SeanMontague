<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\Attribute\LayerFeature;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\LayerFeatureInterface;
use Drupal\geolocation\MapProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides marker icon adjustment.
 */
#[LayerFeature(
  id: 'leaflet_marker_icon',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Marker Icon Adjustment'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Icon properties.'),
  type: 'leaflet'
)]
class LeafletMarkerIcon extends LayerFeatureBase implements ContainerFactoryPluginInterface {

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
      'icon_size' => [
        'width' => NULL,
        'height' => NULL,
      ],
      'icon_anchor' => [
        'x' => NULL,
        'y' => NULL,
      ],
      'popup_anchor' => [
        'x' => 0,
        'y' => 0,
      ],
      'marker_shadow_path' => '',
      'shadow_size' => [
        'width' => NULL,
        'height' => NULL,
      ],
      'shadow_anchor' => [
        'x' => NULL,
        'y' => NULL,
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
      '#description' => $this->t('Set relative or absolute path to custom marker icon. Tokens supported. Empty for default. Attention: In views contexts, additional icon source options are available in the style settings.'),
      '#default_value' => $settings['marker_icon_path'],
    ];

    $form['icon_size'] = [
      '#type' => 'item',
      '#description' => $this->t('Size of the icon image in pixels.'),
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Icon Size - Width'),
        '#default_value' => $settings['icon_size']['width'],
        '#min' => 0,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Icon Size - Height'),
        '#default_value' => $settings['icon_size']['height'],
        '#min' => 0,
      ],
    ];

    $form['icon_anchor'] = [
      '#type' => 'item',
      '#description' => $this->t('The coordinates of the "tip" of the icon (relative to its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location. Centered by default if size is specified.'),
      'x' => [
        '#type' => 'number',
        '#title' => $this->t('Icon Anchor - X'),
        '#default_value' => $settings['icon_anchor']['x'],
      ],
      'y' => [
        '#type' => 'number',
        '#title' => $this->t('Icon Anchor - Y'),
        '#default_value' => $settings['icon_anchor']['y'],
      ],
    ];

    $form['popup_anchor'] = [
      '#type' => 'item',
      '#description' => $this->t('The coordinates of the point from which popups will "open", relative to the icon anchor.'),
      'x' => [
        '#type' => 'number',
        '#title' => $this->t('Popup Anchor - X'),
        '#default_value' => $settings['popup_anchor']['x'],
      ],
      'y' => [
        '#type' => 'number',
        '#title' => $this->t('Popup Anchor - Y'),
        '#default_value' => $settings['popup_anchor']['y'],
      ],
    ];

    $form['marker_shadow_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shadow path'),
      '#description' => $this->t('Set relative or absolute path to custom marker shadow. Tokens supported. Empty for default. Attention: In views contexts, additional shadow source options are available in the style settings.'),
      '#default_value' => $settings['marker_shadow_path'],
    ];

    $form['shadow_size'] = [
      '#type' => 'item',
      '#description' => $this->t('Size of the shadow image in pixels.'),
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Shadow Size - Width'),
        '#default_value' => $settings['shadow_size']['width'],
        '#min' => 0,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Shadow Size - Height'),
        '#default_value' => $settings['shadow_size']['height'],
        '#min' => 0,
      ],
    ];

    $form['shadow_anchor'] = [
      '#type' => 'item',
      '#description' => $this->t('The coordinates of the "tip" of the shadow (relative to its top left corner) (the same as iconAnchor if not specified).'),
      'x' => [
        '#type' => 'number',
        '#title' => $this->t('Shadow Anchor - X'),
        '#default_value' => $settings['shadow_anchor']['x'],
      ],
      'y' => [
        '#type' => 'number',
        '#title' => $this->t('Shadow Anchor - Y'),
        '#default_value' => $settings['shadow_anchor']['y'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {
    $render_array = parent::alterLayer($render_array, $layer_id, $feature_settings, $context);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                $this->getPluginId() => [
                  'iconSize'     => [
                    'width' => (int) $feature_settings['icon_size']['width'],
                    'height' => (int) $feature_settings['icon_size']['height'],
                  ],
                  'iconAnchor'   => [
                    'x' => (int) $feature_settings['icon_anchor']['x'],
                    'y' => (int) $feature_settings['icon_anchor']['y'],
                  ],
                  'popupAnchor'  => [
                    'x' => (int) $feature_settings['popup_anchor']['x'],
                    'y' => (int) $feature_settings['popup_anchor']['y'],
                  ],
                  'shadowSize' => [
                    'width' => (int) $feature_settings['shadow_size']['width'],
                    'height' => (int) $feature_settings['shadow_size']['height'],
                  ],
                  'shadowAnchor' => [
                    'x' => (int) $feature_settings['shadow_anchor']['x'],
                    'y' => (int) $feature_settings['shadow_anchor']['y'],
                  ],
                ],
              ],
            ],
          ],
        ],
      ]
    );

    if (!empty($feature_settings['marker_icon_path'])) {
      $iconPath = $this->token->replace($feature_settings['marker_icon_path'], $context);
      $iconUrl = $this->fileUrlGenerator->generateString($iconPath);
      $render_array['#attached']['drupalSettings']['geolocation']['maps'][$render_array['#id']][$this->getPluginId()]['markerIconPath'] = $iconUrl;
    }

    if (!empty($feature_settings['marker_shadow_path'])) {
      $shadowPath = $this->token->replace($feature_settings['marker_shadow_path'], $context);
      $shadowUrl = $this->fileUrlGenerator->generateString($shadowPath);
      $render_array['#attached']['drupalSettings']['geolocation']['maps'][$render_array['#id']][$this->getPluginId()]['markerShadowPath'] = $shadowUrl;
    }

    return $render_array;
  }

}
