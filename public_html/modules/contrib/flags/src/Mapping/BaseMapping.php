<?php

namespace Drupal\flags\Mapping;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides generic mapping to map values to flags using config entities.
 */
abstract class BaseMapping implements FlagMappingInterface {

  /**
   * Array of configuration objects.
   *
   * @var \Drupal\Core\Config\ImmutableConfig[]
   */
  protected $config;

  /**
   * Array of extra CSS classes to add to flags.
   *
   * @var string[]
   */
  protected $extraClasses = [];

  /**
   * Gets config key that holds list of mapping entities.
   *
   * @return string
   *   The config key.
   */
  abstract protected function getConfigKey();

  /**
   * Creates new instance of BaseMapping class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $ids = $config->listAll($this->getConfigKey());

    $this->config = $config->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function map($value) {
    // Unify input data.
    $code = trim(strtolower($value));

    $key = $this->getConfigKey() . '.' . $code;

    if (isset($this->config[$key])) {
      // We make sure that flag is lowercase to match our CSS.
      return strtolower($this->config[$key]->get('flag'));
    }

    return $code;
  }

  /**
   * {@inheritDoc}
   */
  public function getOptionAttributes(array $options = []) {
    $attributes = [];

    foreach ($options as $key) {
      $classes = ['flag', 'flag-' . strtolower($this->map($key))];
      $classes = array_merge($this->getExtraClasses(), $classes);
      $attributes[$key] = new Attribute(['data-class' => $classes]);
    }

    return $attributes;
  }

  /**
   * {@inheritDoc}
   */
  public function getExtraClasses() {
    return $this->extraClasses;
  }

}
