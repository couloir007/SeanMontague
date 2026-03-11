<?php

namespace Drupal\mercury_editor;

use Drupal\layout_paragraphs\Utility\Dialog;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides dialog options for Mercury Editor Ajax commands.
 *
 * @package Drupal\mercury_editor
 */
class DialogService {

  /**
   * The dialog configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * Service constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('mercury_editor.settings')->get('dialog_settings');
  }

  /**
   * Returns an array of dialog settings for modal edit forms.
   *
   * @param array $context
   *   The context array.
   *
   * @return array
   *   The modal settings.
   */
  public function dialogSettings(array $context = []) {

    $modal_settings = $this->config['_defaults'];
    if (!empty($context['dialog']) && !empty($this->config[$context['dialog']])) {
      $modal_settings = array_merge($modal_settings, $this->config[$context['dialog']]);
    }
    if (!empty($context['layout'])) {
      $modal_settings['target'] = Dialog::dialogId($context['layout']);
    }

    return $modal_settings ?? [];
  }

}
