<?php

namespace Drupal\geolocation\Plugin\geolocation\DataLayerProvider;

use Drupal\geolocation\Attribute\DataLayerProvider;
use Drupal\geolocation\DataLayerProviderBase;
use Drupal\geolocation\DataLayerProviderInterface;
use Drupal\geolocation\ViewsContextTrait;

/**
 * Provides default layer.
 */
#[DataLayerProvider(id: 'geolocation_views_attachment_layer',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Views Attachment'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Attached view providing geodata. Can inherit filters.'))]
class GeolocationViewsAttachment extends DataLayerProviderBase implements DataLayerProviderInterface {

  use ViewsContextTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel(string $data_layer_option_id, array $settings = [], ?array $context = NULL): string {
    if (empty($context['views_style'])) {
      return parent::getLabel($data_layer_option_id, $settings, $context);
    }

    /** @var \Drupal\geolocation\Plugin\views\style\CommonMap $views_style */
    $views_style = $context['views_style'];

    return $views_style->view->displayHandlers->get($data_layer_option_id)?->display['display_title'] ?? parent::getLabel($data_layer_option_id, $settings, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    if (empty($context['views_style'])) {
      return [];
    }

    /** @var \Drupal\geolocation\Plugin\views\style\CommonMap $views_style */
    $views_style = $context['views_style'];

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $views_style->view;

    $options = [];

    foreach ($view->displayHandlers as $display) {
      if ($display->getPluginId() !== 'geolocation_layer') {
        continue;
      }

      $options[$display->display['id']] = [
        'name' => $display->display['display_title'] . " - " . $this->getPluginDefinition()['name'],
        'description' => $this->getPluginDefinition()['description'],
        'toggleable' => FALSE,
      ];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLayerId(string $data_layer_option_id = 'default'): string {
    return $data_layer_option_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerRenderData(string $data_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    if (empty($context['view'])) {
      return [];
    }

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $context['view'];

    /** @var ?\Drupal\geolocation\Plugin\views\display\GeolocationAttachmentLayer $display */
    $display = $view->displayHandlers->get($data_layer_option_id);

    if (!$display) {
      return [];
    }

    return $display->getOption('geolocation_views_attachment_layer_build') ?? [];
  }

}
