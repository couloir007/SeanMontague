<?php

namespace Drupal\geolocation\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a LayerFeature annotation object.
 *
 * @deprecated in geolocation:4.0.0 and is removed from geolocation:4.0.1. Use Attribute instead.
 * @see https://www.drupal.org/project/geolocation/issues/3525013
 *
 * @see \Drupal\geolocation\LayerFeatureManager
 * @see plugin_api
 *
 * @Annotation
 */
class LayerFeature extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The name of the LayerFeature.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $name;

  /**
   * The description of the LayerFeature.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The map type supported by this LayerFeature.
   *
   * @var string
   */
  public string $type;

}
