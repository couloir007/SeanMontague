<?php

namespace Drupal\geolocation\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a TileLayer annotation object.
 *
 * @deprecated in geolocation:4.0.0 and is removed from geolocation:4.0.1. Use Attribute instead.
 * @see https://www.drupal.org/project/geolocation/issues/3525013
 *
 * @see \Drupal\geolocation\TileLayerManager
 * @see plugin_api
 *
 * @Annotation
 */
class TileLayerProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The name of the MapProvider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $name;

  /**
   * The description of the MapProvider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

}
