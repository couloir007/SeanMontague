<?php

namespace Drupal\geolocation\Plugin\geolocation\DataLayerProvider;

use Drupal\geolocation\Attribute\DataLayerProvider;
use Drupal\geolocation\DataLayerProviderBase;
use Drupal\geolocation\DataLayerProviderInterface;

/**
 * Provides default layer.
 */
#[DataLayerProvider(id: 'geolocation_default_layer',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Map Default'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('This is the content of the map itself without any additional data.'))]
class DefaultLayer extends DataLayerProviderBase implements DataLayerProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getLayerRenderData(string $data_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(string $data_layer_option_id, array $settings = [], ?array $context = NULL): string {
    return $this->t('Default');
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    return [
      'default' => [
        'name' => $this->getPluginDefinition()['name'],
        'description' => $this->getPluginDefinition()['description'],
        'toggleable' => FALSE,
        'default_weight' => -1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getJavascriptModulePath(): string {
    return base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/DataLayerProvider/DefaultLayer.js';
  }

}
