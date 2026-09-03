<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Multipoint.
 *
 * @property \Drupal\geolocation_geometry\GeometryType\Point[] $components
 */
class MultiPoint extends Collection {

  /**
   * Constructor.
   */
  public function __construct(array $components) {
    parent::__construct();

    foreach ($components as $comp) {
      if (!($comp instanceof Point)) {
        throw new \Exception($this->type . " can only contain Point elements");
      }
    }
    $this->components = $components;
  }

  /**
   * {@inheritdoc}
   */
  public function equals(GeometryTypeInterface $geometry): bool {
    if (get_class($geometry) != get_class($this)) {
      return FALSE;
    }

    if (count($this->components) != count($geometry->components)) {
      return FALSE;
    }

    foreach (range(0, count($this->components) - 1) as $count) {
      if (!$this->components[$count]->equals($geometry->components[$count])) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
