<?php

namespace Drupal\geolocation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Location Base.
 *
 * @package Drupal\geolocation
 */
abstract class LocationBase extends PluginBase implements LocationInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LocationInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

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
    $default_settings = $this->getDefaultSettings();
    return array_replace_recursive($default_settings, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(string $location_option_id, array $settings = [], array $context = []): array {
    return [];
  }

  /**
   * Validate Feature Form.
   *
   * @param array $values
   *   Feature values.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   */
  public function validateSettingsForm(array $values, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function getAvailableLocationOptions(array $context = []): array {
    return [
      $this->getPluginId() => $this->getPluginDefinition()['name'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates(string $location_option_id, array $location_option_settings, array $context = []): ?array {
    return [];
  }

}
