<?php

namespace Drupal\geolocation\Plugin\geolocation\DataLayerProvider;

use Drupal\geolocation\Attribute\DataLayerProvider;
use Drupal\geolocation\DataLayerProviderBase;
use Drupal\geolocation\DataLayerProviderInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Provides default layer.
 */
#[DataLayerProvider(id: 'geolocation_views_data_layer',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation Views Data Layer'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Views with Geolocation Data Layer Display. Cannot inherit filters.'))]
class GeolocationViewsDataLayer extends DataLayerProviderBase implements DataLayerProviderInterface {

  /**
   * View.
   *
   * @var \Drupal\views\ViewExecutable|null
   */
  protected ViewExecutable|null $view;

  /**
   * {@inheritdoc}
   */
  public function getLabel(string $data_layer_option_id, array $settings = [], ?array $context = NULL): string {
    if (isset($this->view)) {
      return $this->view->getTitle();
    }

    return $this->t('Views Data Layer');
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerOptions(?array $context = NULL): array {
    $options = [];

    /** @var \Drupal\views\Entity\View[] $views */
    $views = \Drupal::entityTypeManager()
      ->getStorage('view')
      ->loadByProperties([
        'status' => TRUE,
        "display.*.display_plugin" => 'geolocation_data_layer',
      ]);

    foreach ($views as $view_id => $view) {
      foreach ($view->get('display') as $display_id => $display) {
        if ($display['display_plugin'] != "geolocation_data_layer") {
          continue;
        }

        $options[$view_id . '|' . $display_id] = [
          'name' => $view->label() . ' - ' . $display['display_title'],
          'description' => $this->getPluginDefinition()['description'],
        ];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLayerId(string $data_layer_option_id = 'default'): string {
    [, $id_by_views] = explode('|', $data_layer_option_id);
    return $id_by_views;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, string $data_layer_option_id = 'default', array $settings = [], array $context = []): array {
    [$view_id] = explode('|', $data_layer_option_id);

    $view = Views::getView($view_id);

    if (!$view) {
      return $render_array;
    }

    $this->view = $view;

    return parent::alterMap($render_array, $data_layer_option_id, $settings, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getLayerRenderData(string $data_layer_option_id = 'default', array $settings = [], ?array $context = NULL): array {
    [, $display_id] = explode('|', $data_layer_option_id);

    if (!$this->view->setDisplay($display_id)) {
      return [];
    }

    if (!$this->view->executed) {
      if (!$this->view->execute($display_id)) {
        return [];
      }
    }

    return $this->view->display_handler->buildRenderable();
  }

}
